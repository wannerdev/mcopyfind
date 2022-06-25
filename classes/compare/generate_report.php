<?php

namespace plagiarism_mcopyfind\compare;

use DateTime;
use plagiarism_mcopyfind\compare\Document;

class generate_report{

    public $settings;
    public $m_fLog; //log file reference
    public $m_fMatchHtml; //html file reference
    public $m_StartTicks; //time when the comparison started
    public $m_MatchingDocumentPairs=0; //number of matching document pairs
    public $m_szSoftwareName;

    public function __construct($_settings){
        $this->wordHash = array();
        $this->wordNumber = 0;
        $this->realwords = 0;
        $this->m_CompareStep=1000;
        if (PHP_OS_FAMILY === "Windows") {
            $this->m_szReportFolder = "C:\\reports\\"; //path folder where the report will be saved 
        } elseif (PHP_OS_FAMILY === "Linux") {  
            $this->m_szReportFolder = "/var/moodledata/mcopyfind/reports/"; //path folder 
        }								
        $this->szfilename="wcopy.log";									// file 
        $this->m_szSoftwareName="mcopyfind";
        $this->settings= $_settings;

        $this->SetupReport();
    }

    function SetupReport(){
        $this->m_StartTicks =new DateTime();
        //=$date->format($date::RSS);
        if(!is_dir($this->m_szReportFolder)){
            mkdir($this->m_szReportFolder, 0700);
        }
        $szfilename = $this->m_szReportFolder."log.txt";
        $this->m_fLog= fopen($szfilename,"w");				// create and open log text file
        if($this->m_fLog == NULL) return "ERR_CANNOT_OPEN_LOG_FILE";
        fprintf ($this->m_fLog, "Starting Report Files\n". $this->m_StartTicks->format($this->m_StartTicks::RSS));

        $szfilename = $this->m_szReportFolder ."matches.txt";
        $m_fMatch= fopen($szfilename, "w");				// create and open main comparison report text file
        if($m_fMatch == NULL) return "ERR_CANNOT_OPEN_COMPARISON_REPORT_TXT_FILE";
    
        $szfilename=$this->m_szReportFolder."matches.html";
        $this->m_fMatchHtml=fopen( $szfilename, "w");			// create and open main comparison report html file
        if($this->m_fMatchHtml == NULL) return "ERR_CANNOT_OPEN_COMPARISON_REPORT_HTML_FILE";
        
        fprintf($this->m_fMatchHtml,"<html><title>File Comparison Report</title><body><H2>File Comparison Report</H2>\n");
        fprintf($this->m_fMatchHtml,"<H3>Produced by ". $this->m_szSoftwareName ." with These Settings:</H3><br><blockquote>Shortest Phrase to Match: ".$this->settings->m_PhraseLength ."\n");
        fprintf($this->m_fMatchHtml,"<br>Fewest Matches to Report: ".$this->settings->m_WordThreshold."\n");
        if($this->settings->m_bIgnorePunctuation) fprintf($this->m_fMatchHtml,"<br>Ignore Punctuation: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Ignore Punctuation: No\n");
        if($this->settings->m_bIgnoreOuterPunctuation) fprintf($this->m_fMatchHtml,"<br>Ignore Outer Punctuation: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Ignore Outer Punctuation: No\n");
        if($this->settings->m_bIgnoreNumbers) fprintf($this->m_fMatchHtml,"<br>Ignore Numbers: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Ignore Numbers: No\n");
        if($this->settings->m_bIgnoreCase) fprintf($this->m_fMatchHtml,"<br>Ignore Letter Case: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Ignore Letter Case: No\n");
        if($this->settings->m_bSkipNonwords) fprintf($this->m_fMatchHtml,"<br>Skip Non-Words: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Skip Non-Words: No\n");
        if($this->settings->m_bSkipLongWords) fprintf($this->m_fMatchHtml,"<br>Skip Words Longer Than %d Characters: Yes\n".$this->settings->m_SkipLength);
        else fprintf($this->m_fMatchHtml,"<br>Skip Long Words: No\n");
        fprintf($this->m_fMatchHtml,"<br>Most Imperfections to Allow: \n".$this->settings->m_MismatchTolerance);
        fprintf($this->m_fMatchHtml,"<br>Minimum %% of Matching Words: \n". $this->settings->m_MismatchPercentage);
        fprintf($this->m_fMatchHtml,"</blockquote><br><br><table border='1' cellpadding='5'><tr><td align='center'>Perfect Match</td><td align='center'>Overall Match</td><td align='center'>View Both Files</td><td align='center'>File </td><td align='center'>File R</td></tr>");
   
    }

    // function generateReport(Document $inputDoc, $MatchAnchor, $words, $href)
    // {
    //     $this->SetupReport();
    //     $wordcount=0;								// current word number
    //     // $word;$tword;
    //     // $DelimiterType=DEL_TYPE_WHITE;
    //     // $xMatch;
    //     // $xAnchor;
    
    //     // $LastMatch=WORD_UNMATCHED;
    //     // $LastAnchor=0;
    
    //     // $iReturn;
    // }

    function FinishReports()
    {
        $date =new DateTime();
        fprintf($this->m_fLog,"Finishing Report Files\n". $date->format($date::RSS) );
        fprintf($this->m_fMatchHtml,"</table>\n");
        if($this->m_MatchingDocumentPairs == 0) fprintf($this->m_fMatchHtml,"<br>". $this->m_szSoftwareName ." found no matching pairs of documents.<br>You may want to lower the thresholds for matching and try again.<br>\n");
        else fprintf($this->m_fMatchHtml,"<br>".$this->m_szSoftwareName." found ".$this->m_MatchingDocumentPairs." matching pairs of documents.<br>\n");
        fprintf($this->m_fMatchHtml,"</body></html>\n");
        fclose($this->m_fMatchHtml);
        $this->m_fMatchHtml=NULL;
        $m_Time= $date->getTimestamp() - $this->m_StartTicks->getTimestamp();

        fprintf($this->m_fLog,"Done. Total Time:". strval($m_Time) ." seconds\n");
        fclose($this->m_fLog);
        $this->m_fLog=NULL;
        return;
    }
}