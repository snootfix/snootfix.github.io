<?php
    
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
    
    include "killme.php";
    // include "lzw.inc.php";
    
    // ENV
    const PROPS_URL = "https://pastebin.com/raw/NPihZzpu";
    const DATA_PATH = "./data/";
    const MIGRATIONS_PATH = "./migrations/";
    const ENCODE = true;
    const ENCODE_RANGE = [0, 99999999];
    const MODE = 0; //0: data; 1: debug; 2: statistics;
    
    $create_process_file = fn($props) => function ($path) use ($props) {
        $output = [];
        
        $src = file_get_contents($path);
        $len = strlen($src);
        $in_encode_range = $len >= ENCODE_RANGE[0] && $len <= ENCODE_RANGE[1];
        $should_encode = ENCODE && $in_encode_range;
        
        $output['path'] = explode('/', substr($path, 7));
        $output['text'] = $should_encode ? (lzw_compress($src)) : $src;
        $output["encoded"] = $should_encode;
        foreach ($props["keywords"] as $keyword) :
            $pattern = "/{$keyword}:.*\n/";
            if (preg_match($pattern, $src, $matches)) :
                $str = $matches[0];
                $value = trim(substr($str, strlen($keyword) + 1));
                $output[$keyword] = $value;
            endif;
        endforeach;
        
        return $output;
    };
    
    $create_process_dir = fn($props) => function ($path) use ($create_process_file, $props) {
        $output = [];
        
        $files = array_slice(scandir($path), 2);
        $process_file = $create_process_file($props);
        foreach ($files as $file) :
            $output[$file] = $process_file($path . '/' . $file);
        endforeach;
        
        return $output;
    };
    
    $process_data = function ($props) use ($create_process_dir) {
        $output = [];
        
        $process_dir = $create_process_dir($props);
        foreach ($props["folders"] as $dir) :
            $output[$dir] = $process_dir(DATA_PATH . $dir);
        endforeach;
        
        return $output;
    };
    
    $generate_base_dataset = function () use ($process_data) {
        $props = json_decode(file_get_contents(PROPS_URL), true);
        
        $output = array(
            "view" => $props["view"],
            "data" => $process_data($props["preprocessor"]),
            "timestamp" => date('Y-m-d H:i:s')
        );
        
        return $output;
    };
    
    $process_migration = function ($path) {
        $output = [];
        
        $src = json_decode(file_get_contents($path), true);
        $folders = array_keys($src);
        
        foreach ($folders as $folder) {
            $output[$folder] = [];
            
            $items = $src[$folder];
            foreach ($items as $item) :
                $filename = $item["path"][1];
                $len = strlen($item["text"]);
                $in_encode_range = $len >= ENCODE_RANGE[0] && $len <= ENCODE_RANGE[1];
                $should_encode = ENCODE && $in_encode_range;
                if ($should_encode) $item["text"] = (lzw_compress($item["text"]));
                $output[$folder][$filename] = $item;
                $output[$folder][$filename]["encoded"] = $should_encode;
            endforeach;
        }
        
        return $output;
    };
    
    $generate_migrations_dataset = function () use ($process_migration) {
        $output = [];
        
        $files = array_slice(scandir(MIGRATIONS_PATH), 2);
        foreach ($files as $file) :
            $output[] = $process_migration(MIGRATIONS_PATH . '/' . $file);
        endforeach;
        
        return $output;
    };
    
    $apply_migration = function ($base, $migration) {
        $output = $base;
        
        $folders = array_keys($output);
        foreach ($folders as $folder) :
            $output[$folder] = array_merge($output[$folder], $migration[$folder]);
        endforeach;
        
        return $output;
    };
    
    //TODO: submit form template & validation
    //TODO: return statistics
    //TODO: return color coding (add color coding to config?)
    $execute = function () use ($generate_base_dataset, $generate_migrations_dataset, $apply_migration) {
        $base_dataset = $generate_base_dataset();
        $migrations_dataset = $generate_migrations_dataset();
        
        foreach ($migrations_dataset as $migration) :
            $base_dataset["data"] = $apply_migration($base_dataset["data"], $migration);
        endforeach;
        
        return $base_dataset;
    };
    
    $render = function ($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo(json_encode($data));
    };
    
    //TODO: thresholds
    $render_statistics = function ($data) {
        $count = 0;
        $avg = 0;
        $sum = 0;
        $min = 2147483647;
        $max = 0;
        $encoded = 0;
        $lessThan1k = 0;
        $lessThan2k = 0;
        $lessThan5k = 0;
        $lessThan8k = 0;
        $lessThan10k = 0;
        $lessThan20k = 0;
        $lessThan30k = 0;
        $lessThan50k = 0;
        $lessThan100k = 0;
        $moreThan100k = 0;
        foreach ($data["data"] as $folder) :
            foreach ($folder as $fic) :
                $len = strlen($fic["text"]);
                if ($fic["encoded"]) $encoded++;
                
                if ($len < 1000) $lessThan1k++;
                elseif ($len < 2000) $lessThan2k++;
                elseif ($len < 5000) $lessThan5k++;
                elseif ($len < 8000) $lessThan8k++;
                elseif ($len < 10000) $lessThan10k++;
                elseif ($len < 20000) $lessThan20k++;
                elseif ($len < 30000) $lessThan30k++;
                elseif ($len < 50000) $lessThan50k++;
                elseif ($len < 100000) $lessThan100k++;
                elseif ($len > 100000) $moreThan100k++;
                
                if ($len < $min) $min = $len;
                if ($len > $max) $max = $len;
                $sum += $len;
                $count++;
            endforeach;
        endforeach;
        
        $avg = round($sum / $count);
        
        echo 'encoding range   ' . ENCODE_RANGE[0] . ' - ' . ENCODE_RANGE[1];
        
        echo '<br/>';
        echo '<br/>';
        
        echo 'min    ' . $min . '<br/>';
        echo 'max    ' . $max . '<br/>';
        echo 'avg    ' . $avg . '<br/>';
        echo 'encoded    ' . $encoded . '<br/>';
        
        echo '<br/>';
        
        echo '<1k    ' . $lessThan1k . '<br/>';
        echo '<2k    ' . $lessThan2k . '<br/>';
        echo '<5k    ' . $lessThan5k . '<br/>';
        echo '<8k    ' . $lessThan8k . '<br/>';
        echo '<10k   ' . $lessThan10k . '<br/>';
        echo '<20k   ' . $lessThan20k . '<br/>';
        echo '<30k   ' . $lessThan30k . '<br/>';
        echo '<50k   ' . $lessThan50k . '<br/>';
        echo '<100k  ' . $lessThan100k . '<br/>';
        echo '>100k  ' . $moreThan100k . '<br/>';
        
        echo '<br/>';
        
        echo 'real char count ' . $sum . '<br/>';
        echo 'estimated char count ' . (
            $lessThan1k * 1000 + 
            $lessThan2k * 2000 + 
            $lessThan5k * 5000 + 
            $lessThan8k * 8000 + 
            $lessThan10k * 10000 + 
            $lessThan20k * 20000 + 
            $lessThan30k * 30000 + 
            $lessThan50k * 50000 + 
            $lessThan100k * 100000
        );
    };
    
    $render_debug = function ($data, $folder, $file) {
        print_r($data["data"][$folder][$file]);
        echo '<br/>';
        echo '<br/>';
        print_r(lzw_decompress(base64_decode($data["data"][$folder][$file]["text"])));
        echo '<br/>';
        echo '<br/>';

        $txt = $data["data"][$folder][$file]["text"];
        $txt_c = lzw_compress($txt);
        $txt_c64 = base64_encode($txt_c);
        //$txt_d = lzw_decompress(base64_decode($txt_c64));
        
        echo strlen($txt);
        echo '<br/>';
        echo strlen($txt_c);
        echo '<br/>';
        echo strlen($txt_c64);
        echo '<br/>';
        echo strlen($txt_d);
        echo '<br/>';
    };
    
    $dataset = $execute();
    
    if (MODE === 0) $render($dataset);
    elseif (MODE === 1) $render_debug($dataset, "Multiple" , "A Better End Chapter 1.txt");
    elseif (MODE === 2) $render_statistics($dataset);
    
