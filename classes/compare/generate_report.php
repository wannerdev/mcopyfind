<?php

namespace plagiarism_mcopyfind\compare;


use DateTime;
use plagiarism_mcopyfind\compare\document;

class generate_report{

    public $settings;
    public $m_fLog; // handle for the log file
    public $m_StartTicks; //time when the comparison started
    public $m_MatchingDocumentPairs=0; //number of matching document pairs
    public $m_szSoftwareName;

    public $m_fMatch;							// handle for comparisons that exceed threshold (output)
	public $m_fMatchHtml;						// handle for comparisons that exceed threshold (output) - html
	public $m_fHtmll;							// handle for output html files					
	public $m_debug;							// flag to include debug output in log file
	public $m_pQWordHash;			// a pointer to a working hash-coded word list
	public $m_pXWordHash;			// a pointer to a temporary hash-coded word list
	public $m_WordsAllocated;					// number of words allocated in the many word-related arrays
    public $m_bBriefReport;

    public $m_pDocS;				// All documents
    public $m_pDocL;				// Left document object
    public $m_pDocR;				// Right document object

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
        fprintf ($this->m_fLog, "Starting Report Files \n". $this->m_StartTicks->format($this->m_StartTicks::RSS) ."\n");

        $szfilename = $this->m_szReportFolder ."matches.txt";
        $this->m_fMatch= fopen($szfilename, "w");				// create and open main comparison report text file
        if($this->m_fMatch == NULL) return "ERR_CANNOT_OPEN_COMPARISON_REPORT_TXT_FILE";
    
        $szfilename=$this->m_szReportFolder."matches.htm";
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

    function ReportMatchedPair(compare_functions $compare, $docL, $docR)
    {
        		
        $this->m_pDocL = $docL;				
         $this->m_pDocR = $docR;
         $hrefL[1000]="";
         $hrefR[1000]="";					// href for the Left and Right html files
         $hrefB[1000]="";					// href from frame file for side-by-side viewing
         $dstring="";						// character buffer for document name strings
    
        $indoc = new document();			// CInputDocument class to handle inputting the document
        $indoc->m_bBasic_Characters = $this->settings->m_bBasic_Characters;		// inform the input document about whether we're using Basic Characters only
    
        $iReturn=0;
    
        // report number of matching words in the Match and Log files
        fprintf($this->m_fMatch, $compare->m_MatchingWordsPerfect." " . $compare->m_MatchingWordsTotalL . $compare->m_MatchingWordsTotalR . $this->m_pDocL->filename.$this->m_pDocR->filename);
        fprintf($this->m_fLog, "Match:". $compare->m_MatchingWordsPerfect. $compare->m_MatchingWordsTotalL . $compare->m_MatchingWordsTotalR. $this->m_pDocL->filename.$this->m_pDocR->filename ."\n");
        fflush($this->m_fLog);
    
    
        $Backslash = strpos($this->m_pDocL->filename, '\\');
        $Length = strlen($this->m_pDocL->filename);
        if($Backslash == -1) $m_szDocL = $this->m_pDocL->filename;
        else $m_szDocL = substr($this->m_pDocL->filename,(-($Length - $Backslash - 1)));
    
        $Backslash = strpos($this->m_pDocR->filename, '\\');
        $Length = strlen($this->m_pDocR->filename);
        if($Backslash == -1) $m_szDocR = $this->m_pDocR->filename;
        else $m_szDocR = substr($this->m_pDocR->filename,(-($Length - $Backslash - 1)));
    
        $hrefL=$m_szDocL;					// generate name for right html filename
        $hrefL.=".".$m_szDocR.".html";
    
        $hrefR=$m_szDocR;					// generate name for right html filename
        $hrefR.="." . $m_szDocL . ".html";
    
        $dstring=strval($this->m_MatchingDocumentPairs);
        $hrefB="SBS.".$m_szDocR. "." . $m_szDocL . "." . $dstring . ".html";
    
    
        $szPerfectMatch =($compare->m_MatchingWordsPerfect. "(" . strval(100*$compare->m_MatchingWordsPerfect/$this->m_pDocL->m_WordsTotal)."L,".  strval(100*$compare->m_MatchingWordsPerfect/$this->m_pDocR->m_WordsTotal)."R)");
        $szOverallMatch=($compare->m_MatchingWordsTotalL . " (". strval(100*$compare->m_MatchingWordsTotalL/$this->m_pDocL->m_WordsTotal). ") L;" . $compare->m_MatchingWordsTotalR . "(" . strval(100*$compare->m_MatchingWordsTotalR/$this->m_pDocR->m_WordsTotal).") R");
        fprintf($this->m_fMatchHtml,
            "<tr><td>%s</td><td>%s</td><td><a href=\"%s\" target=\"_blank\">Side-by-Side</a></td><td>
            <a href=\"%s\" target=\"_blank\">%s</a></td><td><a href=\"%s\" target=\"_blank\">%s</a></td></tr>\n",
            $szPerfectMatch, $szOverallMatch, $hrefB, $hrefL, $m_szDocL, $hrefR, $m_szDocR	);
    

        $dstring = $this->m_szReportFolder .  "\\" . $hrefL;					// generate full path for left html file
        $m_fHtml=fopen($dstring,"w"); 				// create and open left html file
        if($m_fHtml == NULL) return "ERR_CANNOT_OPEN_LEFT_HTML_FILE";
    
        // create header material for left html file
    
        fprintf($m_fHtml,"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n");
        fprintf($m_fHtml,"<html xmlns=\"http://www.w3.org/1999/xhtml\">\n");
        fprintf($m_fHtml,"<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n");
        fprintf($m_fHtml,"<title>Comparison of ".$m_szDocL. "with". $m_szDocR ."(Matched Words =". $compare->m_MatchingWordsPerfect.")</title>\n");
        fprintf($m_fHtml,"<base target='right'>\n");
        fprintf($m_fHtml,"</head>\n");
        fprintf($m_fHtml,"<body>\n");
        
       
        $this->m_pDocL->OpenDocument();
        
                    
        // generate text body of html file, with matching words underlined
        $iReturn = generate_report::DocumentToHtml($indoc,$this->m_MatchMarkL,$this->m_MatchAnchorL,$this->m_pDocL->m_WordsTotal,$hrefR); if($iReturn > -1) return $iReturn;
        
        // $indoc->CloseDocument();								// close document
    
        fprintf($m_fHtml,"\n</body></html>\n");				// complete html file
        fclose($m_fHtml); $m_fHtml=NULL;						// close html file
    
        $dstring=$this->m_szReportFolder;						// generate full path for right html file
        $dstring .= "\\" . $hrefR;
    
        $m_fHtml = fopen($dstring,"w");					// create and open right html file
        if($m_fHtml == NULL) return "ERR_CANNOT_OPEN_RIGHT_HTML_FILE";
        
        // create header material for right html file
    
        fprintf($m_fHtml,"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n");
        fprintf($m_fHtml,"<html xmlns=\"http://www.w3.org/1999/xhtml\">\r\n");
        fprintf($m_fHtml,"<head>\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n");
        fprintf($m_fHtml,"<title>Comparison of %s with %s (Matched Words = %d)</title>\r\n",$m_szDocR,$m_szDocL,$compare->m_MatchingWordsPerfect);
        fprintf($m_fHtml,"<base target='left'>\r\n");
        fprintf($m_fHtml,"</head>\r\n");
        fprintf($m_fHtml,"<body>\r\n");
    
        $iReturn = $indoc->OpenDocument($this->m_pDocR->filename);	// open right document for word input
        if($iReturn > -1)
        {
            // $indoc.CloseDocument();							// close document
            return $iReturn;
        }
                    
        // generate text body of html file, with matching words underlined
    
        $iReturn =  generate_report::DocumentToHtml($indoc,$this->m_MatchMarkR,$this->m_MatchAnchorR,$this->m_pDocR->m_WordsTotal,$hrefL); if($iReturn > -1) return $iReturn;
        // $indoc.CloseDocument();
    
        fprintf($m_fHtml,"\n</body></html>\n");				// complete html file
        fclose($m_fHtml); $m_fHtml=NULL;						// close html file
        
        $dstring = $this->m_szReportFolder; 
        $dstring .= "\\" . $hrefB;
    
        $m_fHtml=fopen($dstring,"w");					// create and open side-by-side html file
        if($m_fHtml == NULL) return "ERR_CANNOT_OPEN_SIDE_BY_SIDE_HTML_FILE";
    
        // create side-by-side wrapper html file
    
        fprintf($m_fHtml,"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n");
        fprintf($m_fHtml,"<html><title>Comparison of %s with %s (Matched Words = %d)</title>\n",$m_szDocR, $m_szDocL, $compare->m_MatchingWordsPerfect);
        fprintf($m_fHtml,"<frameset cols=\"*,*\" frameborder=\"YES\" border=\"1\" framespacing=\"0\">");
        fprintf($m_fHtml,"<frame src=\"%s\" name=\"left\">\n",$hrefL);
        fprintf($m_fHtml,"<frame src=\"%s\" name=\"right\">\n",$hrefR);
        fprintf($m_fHtml,"</frameset><body></body></html>");
    
        fclose($m_fHtml); $m_fHtml=NULL;
    
        return -1;
    }

    function DocumentToHtml(Document $indoc,$MatchMark, $MatchAnchor, $words, $href)
    {
        $wordcount=0;								// current word number

        $word="";								// current word
        $tword[]="";								
        $DelimiterType=DEL_TYPE_WHITE;

        $xMatch=0;
        $xAnchor=0;
        $LastMatch=WORD_UNMATCHED;
        $LastAnchor=0;
        $iReturn=0;

        for($wordcount=0;$wordcount<$words;$wordcount++)	// loop for every word
        {
            $xMatch=$MatchMark[$wordcount];
            $xAnchor=$MatchAnchor[$wordcount];

            if(($LastMatch!=$xMatch) || ($LastAnchor!=$xAnchor))	// check for a change of markup or anchor
            {
                if($LastMatch==WORD_PERFECT) fprintf($this->this->m_fHtml,"</font>");	// close out red markups if they were active
                else if($LastMatch==WORD_FLAW) fprintf($this->m_fHtml,"</font></i>");	// close out green italics if they were active
                else if($LastMatch==WORD_FILTERED)  fprintf($this->m_fHtml,"</font>");	// close out blue markups if they were active

                if($LastAnchor!=$xAnchor)
                {
                    if($LastAnchor>0)
                    {
                        fprintf($this->m_fHtml,"</a>");	// close out any active anchor
                        $LastAnchor=0;
                    }
                    if($xAnchor>0)
                    {
                        if($this->m_bBriefReport && ($wordcount>0) ) fprintf($this->m_fHtml,"</P>\n<P>");	// print a paragraph mark for a new line
                        fprintf($this->m_fHtml,"<a name='%i' href='%s#%i'>",$MatchAnchor[$wordcount],$href,$MatchAnchor[$wordcount]);	// start new anchor
                    }
                }

                if($xMatch==WORD_PERFECT) fprintf($this->m_fHtml,"<font color='#FF0000'>");	// start red for perfection
                else if($xMatch==WORD_FLAW) fprintf($this->m_fHtml,"<i><font color='#007F00'>");	// start green italics for imperfection
                else if($xMatch==WORD_FILTERED)  fprintf($this->m_fHtml,"<font color='#0000FF'>");	// start blue for filtered
            }

            $LastMatch=$xMatch;
            $LastAnchor=$xAnchor;

            while(true)
            {
                if($DelimiterType == DEL_TYPE_EOF) return -1;			// shouldn't happen unless document changed during scan
                $iReturn = $indoc->Words->vocab($word) ;//.GetWord($word,$DelimiterType); if($iReturn > -1) return $iReturn;	// get next word

                $tword=$word;								// copy word to a temporary

                if($this->settings->m_bIgnorePunctuation) Words::WordRemovePunctuation($tword);	// if ignore punctuation is active, remove punctuation
                if($this->settings->m_bIgnoreOuterPunctuation) Words::wordxouterpunct($tword);	// if ignore outer punctuation is active, remove outer punctuation
                if($this->settings->m_bIgnoreNumbers) Words::WordRemoveNumbers($tword);			// if ignore numbers is active, remove numbers
                if($this->settings->m_bIgnoreCase) Words::WordToLowerCase($tword);			// if ignore case is active, remove case
                if($this->settings->m_bSkipLongWords & (strlen($tword) > $this->settings->m_SkipLength) ) continue;	// if skip too-long words is active, skip them
                if($this->settings->m_bSkipNonwords & (!Words::WordCheck($tword)) ) continue;	// if skip nonwords is active, skip them

                break;
            }
        
            if( (!$this->m_bBriefReport) || ($xMatch == WORD_PERFECT) || ($xMatch == WORD_FLAW) )
            {
                $wordLength=strlen($word);						// find length of word
                // If problems with html characters test double encoding
                for($i=0;$i<$wordLength;$i++) fprintf($this->m_fHtml,htmlspecialchars($word[$i])); // print the character, using UTF8 translation
                	
                if($DelimiterType == DEL_TYPE_WHITE) fprintf($this->m_fHtml," ");					// print a blank for white space
                else if($DelimiterType == DEL_TYPE_NEWLINE) fprintf($this->m_fHtml,"<br>");			// print a break for a new line
            }
        }
        if($LastMatch==WORD_PERFECT) fprintf($this->m_fHtml,"</font>");	// close out red markups if they were active
        else if($LastMatch==WORD_FLAW) fprintf($this->m_fHtml,"</font></i>");	// close out green italics if they were active
        else if($LastMatch==WORD_FILTERED)  fprintf($this->m_fHtml,"</font>");	// close out blue markups if they were active
        if($LastAnchor>0) fprintf($this->m_fHtml,"</a>");	// close out any active anchor
        return -1;
    }

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

        fprintf($this->m_fLog,"\nDone. Total Time:". strval($m_Time) ." seconds\n");
        fclose($this->m_fLog);
        $this->m_fLog=NULL;
        return;
    }
}