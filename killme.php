<?php

    function has ($assoc_arr, $key) {
        return array_key_exists($key, $assoc_arr);
    }
    
    function at ($str, $index) {
        return substr($str, $index, 1);
    }
    
    function lzw_compress ($str) {
        if (!$str) return "";
        
        $i; $k; $value;
        $bits_per_char = 16;
        
        $dict = array();
        $dict_out = array();
        
        $c = ""; //char
        $w = ""; //word
        $wc = ""; //carried word char
        
        $elnargein = 2; //wtf r u??
        $dict_size = 3;
        $bits = 2;
        
        $data = array();
        $data_val = 0;
        $data_pos = 0;
        
        for ($k = 0; $k < strlen($str); $k++) {
            $c = at($str, $k);
            
            if (!has($dict, $c)) :
                $dict[$c] = $dict_size++;
                $dict_out[$c] = true;
            endif;
            
            $wc = $w . $c;
            
            if (has($dict, $wc)) :
                $w = $wc;
                continue;
            endif;
            
            // wc was not in dict_out
            if (has($dict_out, $w)) {
                if (at($w, 0) < 256) {
                    for ($i = 0; $i < $bits; $i++) :
                        $data_val = ($data_val << 1);
                        if ($data_pos == $bits_per_char - 1) :
                            $data[] = ($data_val);
                            $data_pos = 0;
                            $data_val = 0;
                        else :
                            $data_pos++;
                        endif;
                    endfor;
                    
                    $value = mb_ord(at($w, 0));
                    
                    for ($i = 0; $i < 8; $i++) :
                        $data_val = ($data_val << 1) | ($value & 1); //what
                        if ($data_pos == $bits_per_char - 1) :
                            $data[] = ($data_val);
                            $data_pos = 0;
                            $data_val = 0;
                        else :
                            $data_pos++;
                        endif;
                        $value = $value >> 1;
                    endfor;
                } else { // w >= 256
                    $value = 1;
                    for ($i = 0; $i < $bits; $i++) :
                        $data_val = ($data_val << 1) | $value; //what
                        if ($data_pos == $bits_per_char - 1) :
                            $data[] = ($data_val);
                            $data_pos = 0;
                            $data_val = 0;
                        else :
                            $data_pos++;
                        endif;
                        $value = 0;
                    endfor;
                    
                    $value = mb_ord(at($w, 0));
                    
                    for ($i = 0; $i < 16; $i++) :
                        $data_val = ($data_val << 1) | ($value & 1); //what
                        if ($data_pos == $bits_per_char - 1) :
                            $data[] = ($data_val);
                            $data_pos = 0;
                            $data_val = 0;
                        else :
                            $data_pos++;
                        endif;
                        $value = $value >> 1;
                    endfor;
                };
                
                $elnargein--;
                if ($elnargein == 0) : 
                    $elnargein = 2 ** $bits;
                    $bits++;
                endif;
                
                unset($dict_out[$w]);
            } else { //w not in dict
                $value = $dict[$w];
                for ($i = 0; $i < $bits; $i++) :
                    $data_val = ($data_val << 1) | ($value & 1); //what
                    if ($data_pos == $bits_per_char - 1) :
                        $data[] = ($data_val);
                        $data_pos = 0;
                        $data_val = 0;
                    else :
                        $data_pos++;
                    endif;
                    $value = $value >> 1;
                endfor;
            }
                
            $elnargein--;
            if ($elnargein == 0) : 
                $elnargein = 2 ** $bits;
                $bits++;
            endif;
            
            $dict[$wc] = $dict_size++;
            $w = strval($c); //without strval?
        }; // k over str
        
        if ($w != "") {
            if (has($dict_out, $w)) {
                if (at($w, 0) < 256) {
                    for ($i = 0; $i < $bits; $i++) :
                        $data_val = ($data_val << 1);
                        if ($data_pos == $bits_per_char - 1) :
                            $data[] = ($data_val);
                            $data_pos = 0;
                            $data_val = 0;
                        else :
                            $data_pos++;
                        endif;
                    endfor;
                    
                    $value = mb_ord(at($w, 0));
                    
                    for ($i = 0; $i < 8; $i++) :
                        $data_val = ($data_val << 1) | ($value & 1); //what
                        if ($data_pos == $bits_per_char - 1) :
                            $data[] = ($data_val);
                            $data_pos = 0;
                            $data_val = 0;
                        else :
                            $data_pos++;
                        endif;
                        $value = $value >> 1;
                    endfor;
                } else { // w >= 256
                    $value = 1;
                    for ($i = 0; $i < $bits; $i++) :
                        $data_val = ($data_val << 1) | $value; //what
                        if ($data_pos == $bits_per_char - 1) :
                            $data[] = ($data_val);
                            $data_pos = 0;
                            $data_val = 0;
                        else :
                            $data_pos++;
                        endif;
                        $value = 0;
                    endfor;
                    
                    $value = mb_ord(at($w, 0));
                    
                    for ($i = 0; $i < 16; $i++) :
                        $data_val = ($data_val << 1) | ($value & 1); //what
                        if ($data_pos == $bits_per_char - 1) :
                            $data[] = ($data_val);
                            $data_pos = 0;
                            $data_val = 0;
                        else :
                            $data_pos++;
                        endif;
                        $value = $value >> 1;
                    endfor;
                }
                
                $elnargein--;
                if ($elnargein == 0) : 
                    $elnargein = 2 ** $bits;
                    $bits++;
                endif;
                
                unset($dict_out[$w]);
            } else { //w not in dict_out
                $value = $dict[$w];
                for ($i = 0; $i < $bits; $i++) :
                    $data_val = ($data_val << 1) | ($value & 1); //what
                    if ($data_pos == $bits_per_char - 1) :
                        $data[] = ($data_val);
                        $data_pos = 0;
                        $data_val = 0;
                    else :
                        $data_pos++;
                    endif;
                    $value = $value >> 1;
                endfor;
            }
            
            $elnargein--;
            if ($elnargein == 0) : 
                $elnargein = 2 ** $bits;
                $bits++;
            endif;
        };
        
        $value = 2;
        
        for ($i = 0; $i < $bits; $i++) :
            $data_val = ($data_val << 1) | ($value & 1); //what
            if ($data_pos == $bits_per_char - 1) :
                $data[] = ($data_val);
                $data_pos = 0;
                $data_val = 0;
            else :
                $data_pos++;
            endif;
            $value = $value >> 1;
        endfor;
        
        while (true) :
            $data_val = ($data_val << 1);
            if ($data_pos == $bits_per_char - 1) :
                $data[] = ($data_val);
                break;
            else :
                $data_pos++;
            endif;
        endwhile;
        
        $output = "";
        for ($i = 0; $i < count($data); $i++) :
            $output .= mb_chr($data[$i]);
        endfor;
            
        return $output;
    }
    