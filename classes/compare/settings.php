<?php

namespace plagiarism_mcopyfind\compare;


 class settings{

    const WORDBUFFERLENGTH=5;
    //Settings
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



    function getMinorEditSettings(){
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


    function getPDFHeaderandFooterSettings(){
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


    function getAbsoluteMatching(){

        $settings = new settings();
        $settings->m_PhraseLength = 6;
        $settings->m_WordThreshold = 100;
        $settings->m_SkipLength = 20;
        $settings->m_MismatchTolerance=0;
        $settings->m_MismatchPercentage = 100;
        $settings->pdfHeader = 0;
        $settings->pdfFooter = 0;
        $settings->m_bBriefReport = false;
        $settings->m_bIgnoreCase = false;
        $settings->m_bIgnoreNumbers = false;
        $settings->m_bIgnoreOuterPunctuation = false;
        $settings->m_bIgnorePunctuation = false;
        $settings->m_bSkipLongWords = false;
        $settings->m_bSkipNonwords = false;
        $settings->m_bBasic_Characters = false;
        return $settings;
    }
}

