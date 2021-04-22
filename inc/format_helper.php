<?php

class PDXSyncFormatHelper {

    const FORMATS = [
        "m2" => "m<sup>2</sup>"
        , "m3" => "m<sup>3</sup>"
        , "EUR/v" => '&euro;/v'
        , "eur/kk" => '&euro;/kk'
    ];

    /**
     * Formats value units so they look good when output as html
     * 
     * @param string $str Text that should be formatted
     * 
     * @return string Returns formated passed content
     */
    static public function formatUnits($str){

        $str = strtr($str, self::FORMATS);

        return $str;
    }

    /**
     * Formats passed text to paragraphs
     * 
     * @param string Text that should be formatted to paragraphs
     * 
     * @return string Text containing paragraphs
     */
    static public function formatParagraphs($str){

        if(trim($str)){
            // double line breaks to paragraphs
            $str = '<p>' . preg_replace('/(\r\n|\n\r|\r|\n){2,}/', '</p><p>', $str) . '</p>';

            // remaining single line breaks to <br> line breaks
            $str = nl2br($str);
        }

        return $str;
    }

    /**
     * @param string $text
     * 
     * @return string Returns passed string where all characters have been converted to html entities
     */
    static public function spamSafe($text){
        if(!strlen($text)) return "";
        $r="";
        foreach(str_split($text) as $v){
            $r.="&#".ord($v).";";
        }
        return $r;
    }
}