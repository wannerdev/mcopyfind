<?php

namespace plagiarism_mcopyfind\compare;


class generate_report{

    function __construct(){
        $this->wordHash = array();
        $this->wordNumber = 0;
        $this->realwords = 0;
        $this->m_CompareStep=1000;
        $this->m_szReportFolder = "C:\\reports";								//path folder 
        $this->szfilename="wcopy.log";									// file 
        $this->softwarename="mcopyfind";
           
    }

    function SetupReport(){
        $m_StartTicks=getdate();
    
        $szfilename = $this->m_szReportFolder."\\log.txt";
        $m_fLog= fopen($szfilename,"w");				// create and open log text file
        if($m_fLog == NULL) return "ERR_CANNOT_OPEN_LOG_FILE";
        fprintf ($m_fLog, "Starting Report Files\n", $m_StartTicks);

        $szfilename = $this->m_szReportFolder ."\\matches.txt";
        $m_fMatch= fopen($szfilename, "w");				// create and open main comparison report text file
        if($m_fMatch == NULL) return "ERR_CANNOT_OPEN_COMPARISON_REPORT_TXT_FILE";
    
        $szfilename=$this->m_szReportFolder."\\matches.html";
        $m_fMatchHtml=fopen( $szfilename, "w");			// create and open main comparison report html file
        if($m_fMatchHtml == NULL) return "ERR_CANNOT_OPEN_COMPARISON_REPORT_HTML_FILE";
        
        fprintf($m_fMatchHtml,"<html><title>File Comparison Report</title><body><H2>File Comparison Report</H2>\n");
        fprintf($m_fMatchHtml,"<H3>Produced by ". $this->softwarename ." with These Settings:</H3><br><blockquote>Shortest Phrase to Match: \n");  //m_PhraseLength
        //define get settings? 
        // fprintf($m_fMatchHtml,"<br>Fewest Matches to Report: %d\n",m_WordThreshold);
        // if(m_bIgnorePunctuation) fwprint($m_fMatchHtml,"<br>Ignore Punctuation: Yes\n");
        // else fwprint($m_fMatchHtml,"<br>Ignore Punctuation: No\n");
        // if(m_bIgnoreOuterPunctuation) fwprint($m_fMatchHtml,"<br>Ignore Outer Punctuation: Yes\n");
        // else fwprint($m_fMatchHtml,"<br>Ignore Outer Punctuation: No\n");
        // if(m_bIgnoreNumbers) fwprint($m_fMatchHtml,"<br>Ignore Numbers: Yes\n");
        // else fwprint($m_fMatchHtml,"<br>Ignore Numbers: No\n");
        // if(m_bIgnoreCase) fwprint($m_fMatchHtml,"<br>Ignore Letter Case: Yes\n");
        // else fwprint($m_fMatchHtml,"<br>Ignore Letter Case: No\n");
        // if(m_bSkipNonwords) fwprintf($m_fMatchHtml,"<br>Skip Non-Words: Yes\n");
        // else fwprint($m_fMatchHtml,"<br>Skip Non-Words: No\n");
        // if(m_bSkipLongWords) fwprint($m_fMatchHtml,"<br>Skip Words Longer Than %d Characters: Yes\n",m_SkipLength);
        // else fwprint($m_fMatchHtml,"<br>Skip Long Words: No\n");
        // fwprint($m_fMatchHtml,"<br>Most Imperfections to Allow: %d\n",m_MismatchTolerance);
        // fwprint($m_fMatchHtml,"<br>Minimum %% of Matching Words: %d\n",m_MismatchPercentage);
        // fwprint($m_fMatchHtml,"</blockquote><br><br><table border='1' cellpadding='5'><tr><td align='center'>Perfect Match</td><td align='center'>Overall Match</td><td align='center'>View Both Files</td><td align='center'>File </td><td align='center'>File R</td></tr>");
   
    }

    function generateReport(Document $inputDoc, $MatchAnchor, $words, $href)
    {
        $this->SetupReport();
        $wordcount=0;								// current word number
        // $word;$tword;
        // $DelimiterType=DEL_TYPE_WHITE;
        // $xMatch;
        // $xAnchor;
    
        // $LastMatch=WORD_UNMATCHED;
        // $LastAnchor=0;
    
        // $iReturn;
    }
}