<?php
    
    const PROPS_URL = "https://pastebin.com/raw/NPihZzpu";
    const DATA_PATH = "./data/";
    
    $create_process_file = fn($props) => function ($path) use ($props) {
        $output = [];
        
        $src = file_get_contents($path);
        
        $output['path'] = explode('/', substr($path, 7));
        $output['text'] = $src;
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
    
    $execute = function () use ($process_data) {
        $props = json_decode(file_get_contents(PROPS_URL), true);
        
        $output = array(
            "view" => $props["view"],
            "data" => $process_data($props["preprocessor"])
        );
        
        return $output;
    };
    
    $data = $execute();
    $json = json_encode($data);
    
    //TODO: return columns, filter options, color coding (add color coding to config?)
    header('Content-Type: application/json; charset=utf-8');
    echo($json);
    
