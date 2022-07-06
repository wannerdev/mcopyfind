<?php

namespace plagiarism_mcopyfind\compare;

use Exception;
use PDF2Text;

const DEL_TYPE_NONE =0;
const DEL_TYPE_WHITE = 1;
const DEL_TYPE_NEWLINE = 2;
const DEL_TYPE_EOF = 3;

include('pdf2Text.php');

class Document
{

    public $m_contentType = "TYPE_UNDEFINED"; // type of document: txt doc, pdf, etc.
    public $m_DocumentType = "DOC_TYPE_UNDEFINED"; // Used document ? old, new, etc.
    public $m_haveFile = false;
    public $m_UTF8 = false;

    public $pWordHash = NULL;
    public $pSortedWordHash = []; // a pointer to the hash-coded word list
    public $pSortedWordNumber = []; // a pointer to the sorted hash-coded word list
    public $m_WordsTotal = 0; // an entry for the number of $words in the lists
    public $firstHash = -1; // an entry for the first word with more than 3 chars

    public $file = null;
    public $path = null;

    // public $wordNumber = 0;
    public $realwords = 0;
    public $words =null;

    public function __construct()
    {
        $this->words = new words();
    }

    function definePath($infile){
        if($infile==null)
            return;
        $this->path = $infile;
        $this->OpenDocument();
    }

    function OpenDocument(){
        $pfilename = $this->path;
        // Check that file exists 
        if( !fopen($pfilename,"r") ) throw new Exception("ERR_CANNOT_FIND_FILE"); // open fails if file is not found

        $index=strpos($pfilename,'.'); // find $filename extention
        $pstr=substr($pfilename,$index+1); // get extension 
        if($pstr == NULL) throw new Exception("ERR_CANNOT_FIND_FILE_EXTENSION"); // open fails if there is no file extension
        

        if( (strcmp($pstr,"htm") == 0 ) || (strcmp($pstr,"html") == 0 ) ) $this->m_contentType="CONTENT_TYPE_HTML";
        else if ( strcmp($pstr,"docx") == 0 ) $this->m_contentType="CONTENT_TYPE_DOCX";
        else if ( strcmp($pstr,"doc") == 0 ) $this->m_contentType="CONTENT_TYPE_DOC";
        else if ( strcmp($pstr,"txt") == 0 ) $this->m_contentType="CONTENT_TYPE_TXT";
        else if ( strcmp($pstr,"pdf") == 0 ) $this->m_contentType="CONTENT_TYPE_PDF";
        else if ( strcmp($pstr,"url") == 0 ) $this->m_contentType="CONTENT_TYPE_URL";
        else $this->m_contentType="CONTENT_TYPE_UNKNOWN";

        if($this->m_contentType == "CONTENT_TYPE_DOCX") return $this->OpenDocx($pfilename);
        else if( $this->m_contentType == "CONTENT_TYPE_HTML" ) return $this->OpenHtml($pfilename);
        else if( $this->m_contentType == "CONTENT_TYPE_TXT") return $this->OpenTxt($pfilename);
        else if( $this->m_contentType == "CONTENT_TYPE_DOC") return $this->OpenDoc($pfilename);
        else if( $this->m_contentType == "CONTENT_TYPE_PDF") return $this->OpenPdf($pfilename);
        else if( $this->m_contentType == "CONTENT_TYPE_URL") return $this->OpenUrl($pfilename);
        else return $this->OpenUnknown($pfilename);;
    }

    function closeDocument(){
        fclose($this->file);
        $this->file = null;
    }

    function OpenHtml($filename)
    {
        $m_filep = fopen($filename,"r");
        if($m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->$this->m_haveFile = true;
        $this->m_UTF8 = true; // assume that the html file is encoded in UTF-8
        return -1;
    }

    function OpenDoc($filename)
    {
        $m_filep = fopen($filename,"rb"); // open in binary read mode
        if($m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        $this->m_UTF8 = false;
        return -1;
    }

    function OpenTxt($filename)
    {
        $m_filep = fopen($filename,"r");
        if($m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        if(fgetc($m_filep) == 0xEF) // check for the BOM to indicate a utf-8 text file
        {
            if(fgetc($m_filep) == 0xBB)
            {
                if(fgetc($m_filep) == 0xBF)
                {
                    $this->m_UTF8 = true;  // found BOM; leave input pointing after the BOM
                    return -1;
                }
            }
        }
        $this->m_UTF8 = false;
        fseek($m_filep,0,SEEK_SET); // not a utf-8 text file, so rewind to the beginning
        return -1;
    }

    //todo translate fully to php
     function OpenUrl($filename)
    {
        $m_filep = fopen($filename,"r");
        if($m_filep == NULL) throw new Exception("ERR_CANNOT_FIND_URL_LINK");
    
        $string=true;
        // $szmessage;
    
        while($string)
        {
            $string=stream_get_contents($m_filep, -1, 255);
            if(strcmp($string[0],"url=",4) == 0)
            {
                fclose($m_filep); $m_filep = NULL;
                $dwHttpRequestFlags = "INTERNET_FLAG_EXISTING_CONNECT";
                $szHeaders[] = "Accept: text/*\r\nUser-Agent: WCopyfind\r\n";
    
                // $dwServiceType;
                // $strServerName;
                // $strObject;
                // $nPort;
                //!AfxParseURL(, dwServiceType, strServerName, strObject, nPort) || dwServiceType != INTERNET_SERVICE_HTTP
                
                if (parse_url($string[4]))
                {
                    fclose($m_filep); $m_filep = NULL;
                    throw new Exception("ERR_CANNOT_ACCESS_URL");
                }
    
                // $m_pSession = new CInternetSession;
                // $m_pServer = m_pSession->GetHttpConnection(strServerName, nPort);
                // $m_pHttpFile = m_pServer->OpenRequest(CHttpConnection::HTTP_VERB_GET,strObject, NULL, 1, NULL, NULL, dwHttpRequestFlags);
                // $m_pHttpFile->AddRequestHeaders(szHeaders);
                // $m_pHttpFile->SendRequest();
                // $DWORD dwRet;
                // $m_pHttpFile->QueryInfoStatusCode(dwRet);
    
                // if (dwRet >= 300)
                // {
                //     fclose(m_filep); m_filep = NULL;
                //     return ERR_CANNOT_ACCESS_URL;
                // }
                // m_bInternet = true;
    
                // DWORD dwQuery;
                // $szreturn;
                // dwQuery=HTTP_QUERY_CONTENT_TYPE;
                // m_pHttpFile->QueryInfo(dwQuery,szreturn);
    
                // if(wcsstr(szreturn,L"text/html") != NULL) m_contentType=CONTENT_TYPE_HTML;
                // else if(wcsstr(szreturn,L"text/plain") != NULL) m_contentType=CONTENT_TYPE_TXT;
                // if(wcsstr(szreturn,L"UTF-8") != NULL) m_UTF8 = true;
                // else m_UTF8 = false;
    
                // m_haveFile = true;
                return -1;
            }
        }
        throw new Exception("ERR_CANNOT_ACCESS_URL");
    }

        function OpenUnknown($filename)
    {
        $m_filep=fopen($filename,"r");
        if($m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        $this->m_UTF8 = false;
        return -1;
    }

    //todo translate fully to php, search for docx handler in php
    function OpenDocx($filename)
    {
        // $filename;
        // $filenameLength;
        // wcstombs_s($filenameLength, $filename, 256, $filename, _TRUNCATE); // convert wide-character $filename to byte $filename

        // $m_docxZipArchive = unzOpen($filename);
        // if ($m_docxZipArchive == NULL)
        // {
        //     $m_filep = NULL;
        //     throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE";
        // }

        // $rv = unzLocateFile(m_docxZipArchive, "word/document.xml", NULL);
        // if(rv != UNZ_OK)
        // {
        //     unzClose(m_docxZipArchive);
        //     m_docxZipArchive=NULL;
        //     $m_filep = NULL;
        //     return ERR_BAD_DOCX_FILE;
        // }
        // unzOpenCurrentFile(m_docxZipArchive);
        // $this->m_haveFile = true;
        // $m_UTF8 = true;
        // m_ByteIndexDocx=0; // start at first byte
        // m_ByteCountDocx=0; // there are currently zero bytes
        // $m_filep = 1; // indicate that the file was found?
        return -1;
    }

    function OpenPdf($filename)
    { 
        $m_filep = fstat($filename,"rb");
        if($m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE"); // open fails if file didn't actually open
        if($_FILES['file']['type']=="application/pdf") {
            $a = new PDF2Text();
            $a->setFilename($filename);
            $a->decodePDF();
            return $a->output();
        }
        
        /**
         *       if($m_pdftotext) // pdftotext program exists, so use it to pipe text to this program
         *    assemble command line, e.g.: ""C:\\fullpath\\pdftotext.exe" -enc UTF-8 "C:\\anotherpath\\xin.pdf" - "
         *   // where the final '-' indicates that the output should be piped to a readable input pipe
         *   // both $filenames (pdftotext.exe and xxx.pdf) need to be in quotes, in case they contain spaces, and the entire command must also be in quotes.
         *   $commandLine="\"\"". $m_pdftotextFile  . "\"\" -enc UTF-8 \"" . $filename . "\" - \""; // assemble command 
         *
         *   if(($m_filep = _wpopen($commandLine,"r")) == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE"; // if the pipe don't form, command filed.
         *   $this->m_haveFile = true;
         *   $this->m_UTF8 = true;
         *
         *   return -1; // we should now have the text portion of the PDF file as a readable file attached to $m_filep
         *}
         * else // don't have pdftotext program avialable, so use default pdf function
         */
    }
    

    static function documentToHtml($indoc,  $MatchMark, $MatchAnchor,  $words, $href)
    {
        $m_fHtml= fopen($indoc->file.".html", "w");				// create and open main comparison report text file
        $wordcount=0;								// current word number

        $word[256]="";
        $tword[256]="";
        $DelimiterType=DEL_TYPE_WHITE;

        $xMatch="";
        $xAnchor="";

        $LastMatch=WORD_UNMATCHED;
        $LastAnchor=0;

        $iReturn=0;

        for($wordcount=0;$wordcount<$words;$wordcount++)	// loop for every word
        {
            $xMatch=$MatchMark[$wordcount];
            $xAnchor=$MatchAnchor[$wordcount];

            if(($LastMatch!=$xMatch) || ($LastAnchor!=$xAnchor))	// check for a change of markup or anchor
            {
                if($LastMatch==WORD_PERFECT) fprintf($m_fHtml,"</font>");	// close out red markups if they were active
                else if($LastMatch==WORD_FLAW) fprintf($m_fHtml,"</font></i>");	// close out green italics if they were active
                else if($LastMatch==WORD_FILTERED)  fprintf($m_fHtml,"</font>");	// close out blue markups if they were active

                if($LastAnchor!=$xAnchor)
                {
                    if($LastAnchor>0)
                    {
                        fprintf($m_fHtml,"</a>");	// close out any active anchor
                        $LastAnchor=0;
                    }
                    if($xAnchor>0)
                    {
                        if(settings::$m_bBriefReport && ($wordcount>0) ) fprintf($m_fHtml,"</P>\n<P>");	// pr$a paragraph mark for a new line
                        fprintf($m_fHtml,"<a name='%i' $href='%s#%i'>",$MatchAnchor[$wordcount],$href,$MatchAnchor[$wordcount]);	// start new anchor
                    }
                }

                if($xMatch==WORD_PERFECT) fprintf($m_fHtml,"<font color='#FF0000'>");	// start red for perfection
                else if($xMatch==WORD_FLAW) fprintf($m_fHtml,"<i><font color='#007F00'>");	// start green italics for imperfection
                else if($xMatch==WORD_FILTERED)  fprintf($m_fHtml,"<font color='#0000FF'>");	// start blue for filtered
            }

            $LastMatch=$xMatch;
            $LastAnchor=$xAnchor;

            while(true)
            {
                if($DelimiterType == DEL_TYPE_EOF) return -1;			// shouldn't happen unless document changed during scan
                //$iReturn = $indoc.GetWord($word,$DelimiterType); if($iReturn > -1) return $iReturn;	// get next word
                $iReturn = fgetcsv($indoc, 0, ' '); //if($iReturn > -1) return $iReturn;	// get next word

                // wcscpy_s($tword,$word);								// copy word to a temporary

                // if(settings::$m_bIgnorePunctuation) WordRemovePunctuation($tword);	// if ignore punctuation is active, remove punctuation
                // if(settings::$m_bIgnoreOuterPunctuation) wordxouterpunct($tword);	// if ignore outer punctuation is active, remove outer punctuation
                // if(settings::$m_bIgnoreNumbers) WordRemoveNumbers($tword);			// if ignore numbers is active, remove numbers
                // if(settings::$m_bIgnoreCase) WordToLowerCase($tword);			// if ignore case is active, remove case
                // if(settings::$m_bSkipLongWords $ (wcslen($tword) > settings::$m_SkipLength) ) continue;	// if skip too-long $words is active, skip them
                // if(settings::$m_bSkipNonwords $ (!WordCheck($tword)) ) continue;	// if skip non$words is active, skip them

                break;
            }
        
            if( (!settings::$m_bBriefReport) || ($xMatch == WORD_PERFECT) || ($xMatch == WORD_FLAW) )
            {
                // $wordLength=wcslen($word);						// find length of word
                // for($i=0;$i<$wordLength;$i++) PrintWCharAsHtmlUTF8($m_fHtml,$word[i]);			// pr$the character, using UTF8 translation
                if($DelimiterType == DEL_TYPE_WHITE) fprintf($m_fHtml," ");					// pr$a blank for white space
                else if($DelimiterType == DEL_TYPE_NEWLINE) fprintf($m_fHtml,"<br>");			// pr$a break for a new line
            }
        }
        if($LastMatch==WORD_PERFECT) fprintf($m_fHtml,"</font>");	// close out red markups if they were active
        else if($LastMatch==WORD_FLAW) fprintf($m_fHtml,"</font></i>");	// close out green italics if they were active
        else if($LastMatch==WORD_FILTERED)  fprintf($m_fHtml,"</font>");	// close out blue markups if they were active
        if($LastAnchor>0) fprintf($m_fHtml,"</a>");	// close out any active anchor
        return ;
    }
}
