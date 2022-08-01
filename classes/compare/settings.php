<?php

namespace plagiarism_mcopyfind\compare;


 class settings{


    const WORDBUFFERLENGTH=5;
    //default Settings, absolute matching with skipping words
    public  $m_PhraseLength = 6;
    public  $m_WordThreshold = 100;
    public  $m_SkipLength = 20;
    public  $m_MismatchTolerance=0;
    public  $m_MismatchPercentage = 100;
    public  $pdfHeader = 0;
    public  $pdfFooter = 0;
    public  $m_bBriefReport = false;
    public  $m_bIgnoreCase = false;
    public  $m_bIgnoreNumbers = false;
    public  $m_bIgnoreOuterPunctuation = false;
    public  $m_bIgnorePunctuation = false;
    public  $m_bSkipLongWords = true;
    public  $m_bSkipNonwords = false;
    public  $m_bBasic_Characters = false;

    static function getRecommendedSettings(){
        // log("Settings not loaded, using recommended <br>");
        return new settings(); 
    }

    /**
     * @param int preset 1 recommended,
     *  2 Minor edit ,3 Header and footer cut off, 4 absolut matching
     */
    static function getPreset($preset){
        
        $settings = new settings();
        //Compare settings
        switch($preset){
            default :{
                $settings=self::getRecommendedSettings();
                break;
            }
            case 2:{
                $settings=self::getMinorEditSettings();
                break;
            }
            case 3:{
                $settings=self::getPDFHeaderandFooterSettings();
                break;
            }
            case  4:{
                $settings=self::getAbsoluteMatching();
                break;
            }
        }
        return $settings;
    }

    static function getMinorEditSettings(){
        $settings = new settings();
        $settings->m_PhraseLength = 6;
        $settings->m_WordThreshold = 80;
        $settings->m_SkipLength = 20;
        $settings->m_MismatchTolerance=2;
        $settings->m_MismatchPercentage = 80;
        $settings->m_bIgnoreNumbers = true;
        $settings->m_bIgnoreCase = true;
        $settings->m_bIgnoreNumbers = true;
        $settings->m_bIgnoreOuterPunctuation = true;
        $settings->m_bIgnorePunctuation = false;
        $settings->m_bSkipLongWords = true;

        return $settings;
    }


    static function getPDFHeaderandFooterSettings(){
        $settings = new settings();
        $settings->m_PhraseLength = 6;
        $settings->m_WordThreshold = 100;
        $settings->m_SkipLength = 20;
        $settings->m_MismatchTolerance=0;
        $settings->m_MismatchPercentage = 100;
        $settings->pdfHeader = 20;
        $settings->pdfFooter = 20;
        $settings->m_bBriefReport = false;
        $settings->m_bIgnoreCase = false;
        $settings->m_bIgnoreNumbers = false;
        $settings->m_bIgnoreOuterPunctuation = false;
        $settings->m_bIgnorePunctuation = false;
        $settings->m_bSkipLongWords = true;
        $settings->m_bSkipNonwords = false;
        $settings->m_bBasic_Characters = false;
        return $settings;
    }


    static function getAbsoluteMatching(){
        $settings = new settings();
        $settings->m_PhraseLength = 6;
        $settings->m_WordThreshold = 100;
        $settings->m_SkipLength = 20;
        $settings->m_MismatchTolerance=0;
        $settings->m_MismatchPercentage = 100;
        $settings->pdfHeader = 0;
        $settings->pdfFooter = 0;
        $settings->m_bBriefReport = true;
        $settings->m_bIgnoreCase = false;
        $settings->m_bIgnoreNumbers = false;
        $settings->m_bIgnoreOuterPunctuation = false;
        $settings->m_bIgnorePunctuation = false;
        $settings->m_bSkipLongWords = false;
        $settings->m_bSkipNonwords = false;
        $settings->m_bBasic_Characters = false;
        return $settings;
    }

    function __toString(){
        $string = "";
        $string .= strval($this->m_PhraseLength) . ';';
        $string .= strval($this->m_WordThreshold) . ';';
        $string .= strval($this->m_SkipLength) . ';';
        $string .= strval($this->m_MismatchTolerance). ';';
        $string .= strval($this->m_MismatchPercentage) . ';';
        $string .= strval($this->pdfHeader) . ';';
        $string .= strval($this->pdfFooter) . ';';        
        $string .= strval($this->m_bBriefReport) . ';';
        $string .= strval($this->m_bIgnoreCase) . ';';
        $string .= strval($this->m_bIgnoreNumbers) . ';';
        $string .= strval($this->m_bIgnoreOuterPunctuation) . ';';
        $string .= strval($this->m_bIgnorePunctuation) . ';';
        $string .= strval($this->m_bSkipLongWords) . ';';
        $string .= strval($this->m_bSkipNonwords) . ';';
        $string .= strval($this->m_bBasic_Characters) . ';';
        return $string;
    }
}

