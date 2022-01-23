<?php
    
    const PROPS_URL = "https://pastebin.com/raw/NPihZzpu";
    const DATA_PATH = "./data/";
    const MIGRATIONS_PATH = "./migrations/";
    
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
                $output[$folder][$filename] = $item;
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
    
    $dataset = $execute();
    $render($dataset);
    
