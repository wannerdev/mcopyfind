<?php

namespace plagiarism_mcopyfind\compare;

use ErrorException;
use Exception;
use IntlChar;
use ZipArchive;

const DEL_TYPE_NONE =0;
const DEL_TYPE_WHITE = 1;
const DEL_TYPE_NEWLINE = 2;
const DEL_TYPE_EOF = 3;

const DOC_TYPE_UNDEFINED = 0;
const DOC_TYPE_OLD = 1;
const DOC_TYPE_NEW = 2;

const WORDMAXIMUMLENGTH = 255;
const WORDBUFFERLENGTH = 256;



class document
{

    public $m_contentType = "TYPE_UNDEFINED"; // type of document: txt doc, pdf, etc.
    public $m_DocumentType = DOC_TYPE_UNDEFINED; // Used document ? old, new, etc.
    public $m_haveFile = false;
    public $m_UTF8 = false;
    public $m_pdftotext;
    
	public $m_gotWord = false;
	public $m_gotChar = false;
	public $m_gotDelimiter = false;

    public $m_pWordHash = []; // hash of unsorted words in document 
    public $pSortedWordHash = []; // a pointer to the hash-coded word list
    public $pSortedWordNumber = []; // a pointer to the sorted hash-coded word list
    public $m_WordsTotal = 0; // an entry for the number of $words in the lists
    public $firstHash = -1; // an entry for the first word with more than 3 chars
    public $m_bBasic_Characters; // how to open it (UTF-8 or not)?

    //Char and Byte attributes
    public $m_BytePointerPdf=0;
    public $m_ByteCountPdf=0;
    
    public $m_ByteIndexDocx=0;
    public $m_ByteCountDocx=0;
    public $m_docxByteBuffer=[];
    public $m_eof=0;

    public $m_char;

    public $m_pHttpFile;
    public $m_bInternet=false;
    
    public $filename = null;
    public $name = null;

    // public $wordNumber = 0;
    public $realwords = 0;
    public $words =null;
    public $contenthash =null;
    public $m_filep=null;
    public $m_fHtml=null;
    public $isRes=false;
    public $settings;

    public function __construct( $filename,  $_settings=null,$file=null, $isAdd=false)
    {
        if($_settings instanceof settings)
        {
            $this->settings = $_settings;
        }
        else
        {
            $this->settings = settings::getRecommendedSettings();
        }

        $this->words = new words(); //not used atm
        if(!$isAdd){
            $this->m_DocumentType=DOC_TYPE_NEW; //Assume it is not additional/ solution document
        }else{
            $this->m_DocumentType=DOC_TYPE_OLD;
        }
        //If we got the resource from moodle
        if(is_resource($file) ){ 
            $this->m_filep=$file;
            $this->isRes=true;
            $this->m_haveFile=true;
            $this->filename=$filename;
            $this->OpenDocument();

        }else{ //If we have the file by path
            $this->definePath($filename);
            $this->OpenDocument();
        }
        if (PHP_OS_FAMILY === "Windows") {
            $this->m_pdftotextFile= __DIR__.'\pdftotext.exe';
        } elseif (PHP_OS_FAMILY === "Linux") {  
            //use different commandlinetool
            $this->m_pdftotextFile= __DIR__.'/pdftotext32';
            if(strlen(decbin(~0)) == 64){
                $this->m_pdftotextFile= __DIR__.'/pdftotext64';
            }

        }
        
    }

    function setcontentHash($hash){
        $this->contenthash=$hash;
    }


    function definePath($infile){
        if($infile==null)
            return;
        $this->filename = $infile;
    }

    function OpenDocument(){
        $pfilename = $this->filename;
        // echo "------------"; 
        // echo getcwd(); //working directory -> with namespace workdir changes
        // echo "------------";
        
        // Check that file exists 
        if( !$this->isRes && !fopen($pfilename,"r") ) throw new Exception("ERR_CANNOT_FIND_FILE"); // open fails if file is not found

        $index=strpos($pfilename,'.'); // find $filename extension
        $this->name = substr($pfilename,0,$index); // get $filename without extension
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

    // function closeDocument(){
    //     if($this->m_filep != null){
    //         fclose($this->m_filep);
    //         $this->m_filep = null;
    //     }
    // }

    function OpenHtml($filename)
    {
        $this->m_filep = fopen($filename,"r");
        if($this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        $this->m_UTF8 = true; // assume that the html file is encoded in UTF-8
        return -1;
    }

    function OpenDoc($filename)
    {
        $this->m_filep = fopen($filename,"rb"); // open in binary read mode
        if($this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        $this->m_UTF8 = false;
        return -1;
    }

    function OpenTxt($filename)
    {
        if($this->m_filep == NULL)  { // no resource given
            $this->m_filep= fopen($filename,"r");
        }
        if($this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        if(ord(fgetc($this->m_filep)) == 0xEF) // check for the BOM to indicate a utf-8 text file
        {
            if(ord(fgetc($this->m_filep)) == 0xBB)
            {
                if(ord(fgetc($this->m_filep)) == 0xBF)
                {
                    $this->m_UTF8 = true;  // found BOM; leave input pointing after the BOM
                    if($this->isRes)fseek($this->m_filep,0,SEEK_SET);
                    return -1;
                }
            }
        }
        $this->m_UTF8 = false;
        fseek($this->m_filep,0,SEEK_SET); // not a utf-8 text file, so rewind to the beginning
        
        return -1;
    }

    //todo translate fully to php
     function OpenUrl($filename)
    {
        $this->m_filep = fopen($filename,"r");
        if($this->m_filep == NULL) throw new Exception("ERR_CANNOT_FIND_URL_LINK");
    
        $string=true;
        // $szmessage;
    
        while($string)
        {
            $string=stream_get_contents($this->m_filep, -1, 255);
            if(strcmp($string[0],"url=",4) == 0)
            {
                fclose($this->m_filep); $this->m_filep = NULL;
                $dwHttpRequestFlags = "INTERNET_FLAG_EXISTING_CONNECT";
                $szHeaders[] = "Accept: text/*\r\nUser-Agent: WCopyfind\r\n";
    
                // $dwServiceType;
                // $strServerName;
                // $strObject;
                // $nPort;
                //!AfxParseURL(, dwServiceType, strServerName, strObject, nPort) || dwServiceType != INTERNET_SERVICE_HTTP
                
                if (parse_url($string[4]))
                {
                    fclose($this->m_filep); $this->m_filep = NULL;
                    throw new Exception("ERR_CANNOT_ACCESS_URL");
                }
    
                // $m_pSession = new CInternetSession;
                // $m_pServer = m_pSession->GetHttpConnection(strServerName, nPort);
                // $this->m_pHttpFile = m_pServer->OpenRequest(CHttpConnection::HTTP_VERB_GET,strObject, NULL, 1, NULL, NULL, dwHttpRequestFlags);
                // $this->m_pHttpFile->AddRequestHeaders(szHeaders);
                // $this->m_pHttpFile->SendRequest();
                // $DWORD dwRet;
                // $this->m_pHttpFile->QueryInfoStatusCode(dwRet);
    
                // if (dwRet >= 300)
                // {
                //     fclose(this-> $this->m_filep); this-> $this->m_filep = NULL;
                //     return ERR_CANNOT_ACCESS_URL;
                // }
                // m_bInternet = true;
    
                // DWORD dwQuery;
                // $szreturn;
                // dwQuery=HTTP_QUERY_CONTENT_TYPE;
                // $this->m_pHttpFile->QueryInfo(dwQuery,szreturn);
    
                // if(wcsstr(szreturn,L"text/html") != NULL) m_contentType=CONTENT_TYPE_HTML;
                // else if(wcsstr(szreturn,L"text/plain") != NULL) m_contentType=CONTENT_TYPE_TXT;
                // if(wcsstr(szreturn,L"UTF-8") != NULL) $this->m_UTF8 = true;
                // else $this->m_UTF8 = false;
    
                // m_haveFile = true;
                return -1;
            }
        }
        throw new Exception("ERR_CANNOT_ACCESS_URL");
    }

    function OpenUnknown($filename)
    {
        $this->m_filep=fopen($filename,"r");
        if($this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        $this->m_UTF8 = false;
        return -1;
    }


    function OpenDocx($input_file)
    {
        // $filename;
        // $filenameLength;
        // wcstombs_s($filenameLength, $filename, 256, $filename, _TRUNCATE); // convert wide-character $filename to byte $filename
        $text="";
        if(!$input_file || !file_exists($input_file)) return false;
            
        $zip = zip_open($input_file);
        $zip = new ZipArchive();
        if ($zip->open($input_file)) {
            $content = $zip->getFromName("word/document.xml");
            $zip->close();
            $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
            $content = str_replace('</w:r></w:p>', "\r\n", $content);

            $text= strip_tags($content);
        }
        

        //For now ugly workaround convert docx to txt
        // $index=strpos($input_file,'.'); // find $filename extension
        // $name = substr($input_file,0,$index); // get $filename without extension
        // $this->m_filep= fopen($name.'.txt',"rw");
        // fwrite($this->m_filep , $kv_strip_texts);
        // $this->OpenTxt($name.'.txt'); 
        // $this->m_contentType="CONTENT_TYPE_TXT"; 
        
        // $this->m_filep= $kv_strip_texts;
        // echo();
        // $this->m_filep=$kv_texts;//fopen($filename,"r");
        // echo('-----------------------------------------------\n');
        // echo($this->m_filep);
        // echo('-----------------------------------------------\n');
        $this->m_haveFile = true;
        $this->m_UTF8 = true;
        $this->m_ByteIndexDocx=0; // start at first byte
        $this->m_ByteCountDocx=0; // there are currently zero $bytes
        return -1;
    }
    
    function OpenRtf($filename)
    {
        $this->m_filep=fopen($filename,"r");
        if($this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        $this->m_UTF8 = false;
        return -1;
    }

    function OpenPdf($filename)
    { 
        // echo ("Filename Openpdf:".$filename); 

        $m_path= __DIR__."\\"; 
        $this->m_pdftotext= file_exists($this->m_pdftotextFile);

        if($this->m_pdftotext){ 
           // pdftotext program exists, so use it 
           // Add header and footer remove margins
            $headerFooter= "";
            if($this->settings->pdfHeader!=0 || $this->settings->pdfFooter !=0 ){
                $headerFooter= "-margint ". strval($this->settings->pdfHeader)." -marginb ". strval($this->settings->pdfFooter)." ";
            }
           
           $commandLine=$this->m_pdftotextFile ." -enc UTF-8 ". $headerFooter ."-eol dos ". $m_path. $filename. " -"; // assemble command
            // echo $commandLine;
           if(($this->m_filep= popen($commandLine,"r")) == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE"); // if the pipe don't form, command filed.
           
           //echo("STREAM:".stream_get_contents($this->m_filep));
           //echo ("CMD: ".$commandLine);
           //$this->filename = "conv_". $this->name .".txt";
           $this->m_haveFile = true;
           $this->m_UTF8 = true;
           $this->m_pdftotext =true;
           return -1; // we should now have the text portion of the PDF file as a readable file attached to $this->m_filep
        }
         else{ // don't have pdftotext program avialable, so use default pdf function
            //don't
         }

        throw  new ErrorException("ERR_NO_PDFPROGRAM");
    }
        
    function Getword(&$word,&$delimiterType)
    {
        if(!$this->m_haveFile ||
         $this->m_filep==null) throw new Exception("ERR_NO_FILE_OPEN Filename:". $this->filename); // if no file is open, failure
        
        $wordLength = 0;
        $this->m_gotWord = false;
        $this->m_gotDelimiter = false;
        $delimiterType = DEL_TYPE_NONE;

        //switch($this->m_contentType){ probably nicer with switch case syntax
        //DocX Needs debugging
        if($this->m_contentType == "CONTENT_TYPE_DOCX")
        {
            while(true)
            {
                if($this->m_gotChar) $this->m_gotChar = false; // check to see if we already have the next character
                else $this->m_char=$this->GetCharDocx();
                
                if($this->m_char < 0 || $this->m_char == false ) // check for EOF encountered
                {
                    //$word[$wordLength]=0; // finish the $word off
                    $delimiterType = DEL_TYPE_EOF;
                    return -1;
                }
                else if($this->m_char == '<') // check for '<' character
                {
                    $tagName = "";
                    $bneedtagName = true;

                    while(true) // read in the entire tag, saving the tag name
                    {
                        $this->m_char=$this->GetCharDocx();
                        if($this->m_char < 0 || $this->m_char == false) // check for EOF encountered
                        {
                            //$word[$wordLength]=0; // finsh the $word off;
                            $delimiterType = DEL_TYPE_EOF;
                            return -1;
                        }
                        else if($this->m_char == '>') break; // found '>' (end of tag), so stop scanning it
                        else if($this->m_char == ' ') $bneedtagName = false; // found ' ' (end of tag name), so stop gathering name
                        else if($bneedtagName) $tagName += $this->m_char;
                    }
				if( strcasecmp($tagName,"w:p" ) == 0) // check for a paragraph tag
				{
					$delimiterType=DEL_TYPE_NEWLINE;
					$this->m_gotDelimiter=true;
				}
				else if( strcasecmp($tagName,"w:tab/" ) == 0) // check for a tab tag
				{
					$delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
					$this->m_gotDelimiter=true;
				}
				continue; // otherwise keep reading
                }
                else if(IntlChar::isspace($this->m_char)) // check for white space
                {
                    $delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
                    $this->m_gotDelimiter=true;
                }
                else if(IntlChar::iscntrl($this->m_char)) continue; // skip other control characters
                else if($this->m_gotDelimiter) // have we just reached the end of one or more delimiters?
                {
                    if($this->m_gotWord) // make sure that we have a $word
                    {
                        //$word[$wordLength]=0; // finish the $word off
                        $this->m_gotChar=true;
                        return -1;
                    }
                    else // these were preliminary delimiters and we will ignore them
                    {
                        $delimiterType=DEL_TYPE_NONE;
                        $this->m_gotDelimiter=false;
                        $this->m_gotChar=true;
                    }
                }
                else if($this->m_char == '&') // skip and ignore &xxx; codes.
                {
                    $ampBuffer=[];
                    $ampBufferCount = 0;
                    while(true)
                    {
                        $this->m_char=$this->GetCharDocx(); // read the next character
                        if( (!IntlChar::isalnum($this->m_char)) && ($this->m_char != '#')) break; // keep reading until we hit a non-alphanumeric, which should be a ';')
                        if ($this->m_char < 0 || $this->m_char == false) break; // we encountered an EOF before the end of the & code
                        if($ampBufferCount < 255)
                        {
                            $ampBuffer[$ampBufferCount] = $this->m_char;
                            $ampBufferCount++;
                        }
                    }
                    $ampBuffer[$ampBufferCount] = 0; // finish off the string
                    $ampBuffer=strval($ampBuffer); //Prob wrong
                    if($ampBuffer[0] == '#')
                    {
                        if($ampBuffer[1] == 'x') sscanf($ampBuffer+2,"%x",$this->m_char); // read in the hexidecial number directly
                        else sscanf($ampBuffer+1,"%d",$this->m_char); // read in the character number directly
                    }
                    else if(strcmp($ampBuffer,"trade") == 0) $this->m_char = 153;
                    else if(strcmp($ampBuffer,"nbsp") == 0) $this->m_char = 160;
                    else if(strcmp($ampBuffer,"iexcl") == 0) $this->m_char = 161;
                    else if(strcmp($ampBuffer,"cent") == 0) $this->m_char = 162;
                    else if(strcmp($ampBuffer,"pound") == 0) $this->m_char = 163;
                    else if(strcmp($ampBuffer,"yen") == 0) $this->m_char = 165;
                    else if(strcmp($ampBuffer,"sect") == 0) $this->m_char = 167;
                    else if(strcmp($ampBuffer,"copy") == 0) $this->m_char = 169;
                    else if(strcmp($ampBuffer,"laquo ") == 0) $this->m_char = 171;
                    else if(strcmp($ampBuffer,"reg") == 0) $this->m_char = 174;
                    else if(strcmp($ampBuffer,"plusmn") == 0) $this->m_char = 177;
                    else if(strcmp($ampBuffer,"micro") == 0) $this->m_char = 181;
                    else if(strcmp($ampBuffer,"para") == 0) $this->m_char = 182;
                    else if(strcmp($ampBuffer,"middot") == 0) $this->m_char = 183;
                    else if(strcmp($ampBuffer,"raquo") == 0) $this->m_char = 187;
                    else if(strcmp($ampBuffer,"iquest") == 0) $this->m_char = 191;
                    else if(strcmp($ampBuffer,"Agrave") == 0) $this->m_char = 192;
                    else if(strcmp($ampBuffer,"Aacute") == 0) $this->m_char = 193;
                    else if(strcmp($ampBuffer,"Acirc") == 0) $this->m_char = 194;
                    else if(strcmp($ampBuffer,"Atilde") == 0) $this->m_char = 195;
                    else if(strcmp($ampBuffer,"Auml") == 0) $this->m_char = 196;
                    else if(strcmp($ampBuffer,"Aring") == 0) $this->m_char = 197;
                    else if(strcmp($ampBuffer,"AElig") == 0) $this->m_char = 198;
                    else if(strcmp($ampBuffer,"Ccedil") == 0) $this->m_char = 199;
                    else if(strcmp($ampBuffer,"Egrave") == 0) $this->m_char = 200;
                    else if(strcmp($ampBuffer,"Eacute") == 0) $this->m_char = 201;
                    else if(strcmp($ampBuffer,"Ecirc") == 0) $this->m_char = 202;
                    else if(strcmp($ampBuffer,"Euml") == 0) $this->m_char = 203;
                    else if(strcmp($ampBuffer,"Igrave") == 0) $this->m_char = 204;
                    else if(strcmp($ampBuffer,"Iacute") == 0) $this->m_char = 205;
                    else if(strcmp($ampBuffer,"Icirc") == 0) $this->m_char = 206;
                    else if(strcmp($ampBuffer,"Iuml") == 0) $this->m_char = 207;
                    else if(strcmp($ampBuffer,"Ntilde") == 0) $this->m_char = 209;
                    else if(strcmp($ampBuffer,"Ograve") == 0) $this->m_char = 210;
                    else if(strcmp($ampBuffer,"Oacute") == 0) $this->m_char = 211;
                    else if(strcmp($ampBuffer,"Ocirc") == 0) $this->m_char = 212;
                    else if(strcmp($ampBuffer,"Otilde") == 0) $this->m_char = 213;
                    else if(strcmp($ampBuffer,"Ouml") == 0) $this->m_char = 214;
                    else if(strcmp($ampBuffer,"Oslash") == 0) $this->m_char = 216;
                    else if(strcmp($ampBuffer,"Ugrave") == 0) $this->m_char = 217;
                    else if(strcmp($ampBuffer,"Uacute") == 0) $this->m_char = 218;
                    else if(strcmp($ampBuffer,"Ucirc") == 0) $this->m_char = 219;
                    else if(strcmp($ampBuffer,"Uuml") == 0) $this->m_char = 220;
                    else if(strcmp($ampBuffer,"szlig") == 0) $this->m_char = 223;
                    else if(strcmp($ampBuffer,"agrave ") == 0) $this->m_char = 224;
                    else if(strcmp($ampBuffer,"aacute ") == 0) $this->m_char = 225;
                    else if(strcmp($ampBuffer,"acirc ") == 0) $this->m_char = 226;
                    else if(strcmp($ampBuffer,"atilde ") == 0) $this->m_char = 227;
                    else if(strcmp($ampBuffer,"auml ") == 0) $this->m_char = 228;
                    else if(strcmp($ampBuffer,"aring ") == 0) $this->m_char = 229;
                    else if(strcmp($ampBuffer,"aelig ") == 0) $this->m_char = 230;
                    else if(strcmp($ampBuffer,"ccedil ") == 0) $this->m_char = 231;
                    else if(strcmp($ampBuffer,"egrave ") == 0) $this->m_char = 232;
                    else if(strcmp($ampBuffer,"eacute ") == 0) $this->m_char = 233;
                    else if(strcmp($ampBuffer,"ecirc ") == 0) $this->m_char = 234;
                    else if(strcmp($ampBuffer,"euml ") == 0) $this->m_char = 235;
                    else if(strcmp($ampBuffer,"igrave ") == 0) $this->m_char = 236;
                    else if(strcmp($ampBuffer,"iacute ") == 0) $this->m_char = 237;
                    else if(strcmp($ampBuffer,"icirc ") == 0) $this->m_char = 238;
                    else if(strcmp($ampBuffer,"iuml ") == 0) $this->m_char = 239;
                    else if(strcmp($ampBuffer,"ntilde ") == 0) $this->m_char = 241;
                    else if(strcmp($ampBuffer,"ograve ") == 0) $this->m_char = 242;
                    else if(strcmp($ampBuffer,"oacute ") == 0) $this->m_char = 243;
                    else if(strcmp($ampBuffer,"ocirc ") == 0) $this->m_char = 244;
                    else if(strcmp($ampBuffer,"otilde ") == 0) $this->m_char = 245;
                    else if(strcmp($ampBuffer,"ouml ") == 0) $this->m_char = 246;
                    else if(strcmp($ampBuffer,"divide") == 0) $this->m_char = 247;
                    else if(strcmp($ampBuffer,"oslash ") == 0) $this->m_char = 248;
                    else if(strcmp($ampBuffer,"ugrave ") == 0) $this->m_char = 249;
                    else if(strcmp($ampBuffer,"uacute ") == 0) $this->m_char = 250;
                    else if(strcmp($ampBuffer,"ucirc ") == 0) $this->m_char = 251;
                    else if(strcmp($ampBuffer,"uuml ") == 0) $this->m_char = 252;
                    else if(strcmp($ampBuffer,"yuml") == 0) $this->m_char = 255;
                    else if(strcmp($ampBuffer,"quot") == 0) $this->m_char = 34;
                    else if(strcmp($ampBuffer,"amp") == 0) $this->m_char = 38;
                    else if(strcmp($ampBuffer,"lt") == 0) $this->m_char = 60;
                    else if(strcmp($ampBuffer,"gt") == 0) $this->m_char = 62;
                    else if(strcmp($ampBuffer,"ndash") == 0) $this->m_char = 8211;
                    else if(strcmp($ampBuffer,"mdash") == 0) $this->m_char = 8212;
                    else if(strcmp($ampBuffer,"lsquo") == 0) $this->m_char = 8216;
                    else if(strcmp($ampBuffer,"rsquo") == 0) $this->m_char = 8217;
                    else if(strcmp($ampBuffer,"ldquo") == 0) $this->m_char = 8220;
                    else if(strcmp($ampBuffer,"rdquo") == 0) $this->m_char = 8221;
                    else if(strcmp($ampBuffer,"euro") == 0) $this->m_char = 8364;
                    else continue; // if we couldn't figure out what this character is, skip it

                    if($wordLength < WORDMAXIMUMLENGTH)
                    {
                        $word[$wordLength]=$this->m_char; // add this character to the $word
                        $wordLength++;
                        $this->m_gotWord=true;
                    }
                }			
                else if($wordLength < WORDMAXIMUMLENGTH)
                {
                    $word[$wordLength]=$this->m_char; // add this character to the $word
                    $wordLength++;
                    $this->m_gotWord=true;
                }
            }
	    }
        else if($this->m_contentType == "CONTENT_TYPE_HTML")
        {
            $binScript = false;
            $binStyle = false;
            $binHeader = false;
            
            while(true)
            {
                if($this->m_gotChar) $this->m_gotChar = false; // check to see if we already have the next character
                else
                {
                    $this->m_char=$this->GetCharHtml(); // get a character
                }

                if($this->m_char < 0 || $this->m_char == false) // check for EOF encountered
                {
                    //$word[$wordLength]=0; // finish the $word off
                    $delimiterType = DEL_TYPE_EOF;
                    return -1;
                }
                if($this->m_char == '<') // check for "<" character
                {
                    $tagName = "";
                    $bneedtagName = true;
                    $tagBody = "";

                    while(true) // read in the entire tag, saving the tag name
                    {
                        $this->m_char=$this->GetCharHtml();
                        if($this->m_char < 0 || $this->m_char == false) // check for EOF encountered
                        {
                            //$word[$wordLength]=0; // finsh the $word off;
                            $delimiterType = DEL_TYPE_EOF;
                            return -1;
                        }
                        else if($this->m_char == '>') break; // found ">" (end of tag), so stop scanning it
                        else if($this->m_char == ' ') $bneedtagName = false; // found " " (end of tag name), so stop gathering name
                        else if($bneedtagName) $tagName +=  $this->m_char; // save tag name
                        else $tagBody +=  $this->m_char; // save tag body
                        if( strcmp($tagName,"!--") == 0 ) // are we starting a comment?
                        {
                            $dashes=0;
                            while(true)
                            {
                                $this->m_char=$this->GetCharHtml(); // read the next character
                                if( ($this->m_char == '>') && ($dashes > 1) ) break; // we found the end of the comment
                                if( $this->m_char < 0 || $this->m_char == false) break; // file ended without a completion of the comment
                                else if( $this->m_char == '-') $dashes++; // we found a dash, so increment the count
                                else $dashes = 0; // not a dash, so return the dash count to zero
                            }
                            break;
                        }
                    }
                    if( ( strcasecmp($tagName,"P") == 0 ) || // look for any tag that should trigger a new line
                        ( strcasecmp($tagName,"BR") == 0 ) ||
                        ( strcasecmp($tagName,"BR/") == 0 ) ||
                        ( strcasecmp($tagName,"UL") == 0 ) ||
                        ( strcasecmp($tagName,"TD") == 0 ) )
                    {
                        $delimiterType=DEL_TYPE_NEWLINE;
                        $this->m_gotDelimiter=true;
                    }
                    else if( strcasecmp($tagName,"SCRIPT") == 0 ) $binScript = true; // starting a script, which we need to ignore
                    else if( strcasecmp($tagName,"/SCRIPT") == 0 ) $binScript = false; // ending a script
                    else if( strcasecmp($tagName,"STYLE") == 0 ) $binStyle = true; // starting a style, which we need to ignore
                    else if( strcasecmp($tagName,"/STYLE") == 0 ) $binStyle = false; // ending a style
                    else if( strcasecmp($tagName,"HEAD") == 0 ) $binHeader = true; // starting a style, which we need to ignore
                    else if( strcasecmp($tagName,"/HEAD") == 0 ) $binHeader = false; // ending a style
                    else if( strcasecmp($tagName,"META") == 0 )
                    {
                        // we have a meta tag, which might change our character set
                        strtolower($tagBody); // make the tag body all lower case
                        if(strstr($tagBody,"charset") > 0) // look for the $word charset
                        {
                            if(strstr($tagBody,"utf-8") > 0) $this->m_UTF8 = true; // look for utf-8
                            else $this->m_UTF8 = false; // otherwise we're in another charset and can't use the utf-8 decoding
                        }
                    }
                    continue; // otherwise keep reading
                }
                if($binScript || $binStyle || $binHeader) continue; // skip if we're in the middle of script or style or header
                else if(IntlChar::isspace($this->m_char)) // check for white space
                {
                    $delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
                    $this->m_gotDelimiter=true;
                }
                else if(IntlChar::iscntrl($this->m_char)) continue; // skip other control characters
                else if($this->m_gotDelimiter) // have we just reached the end of one or more delimiters?
                {
                    if($this->m_gotWord) // make sure that we have a $word
                    {
                        //$word[$wordLength]=0; // finish the $word off
                        $this->m_gotChar=true;
                        return -1;
                    }
                    else // these were preliminary delimiters and we will ignore them
                    {
                        $delimiterType=DEL_TYPE_NONE;
                        $this->m_gotDelimiter=false;
                        $this->m_gotChar=true;
                    }
                }
                else if($this->m_char == '&') // skip and ignore &xxx; codes.
                {
                    $ampBuffer=[];
                    $ampBufferCount = 0;
                    while(true)
                    {
                        $this->m_char=$this->GetCharHtml(); // read the next character
                        if( (!IntlChar::isalnum($this->m_char)) && ($this->m_char != '#')) break; // keep reading until we hit a non-alphanumeric, which should be a ';')
                        if( $this->m_char < 0 || $this->m_char == false) break; // we hit an EOF before the end of the & code
                        if($ampBufferCount < 255)
                        {
                            $ampBuffer[$ampBufferCount] = $this->m_char;
                            $ampBufferCount++;
                        }
                    }
                    $ampBuffer[$ampBufferCount] = 0; // finish off the string
                    $ampBuffer = strval($ampBuffer); // convert to a string
                    if($ampBuffer[0] == '#')
                    {
                        if($ampBuffer[1] == 'x') sscanf($ampBuffer+2,"%x",$this->m_char); // read in the hexidecial number directly
                        else sscanf($ampBuffer+1,"%d",$this->m_char); // read in the character number directly
                    }

                    else if(strcmp($ampBuffer,"trade") == 0) $this->m_char = 153;
                    else if(strcmp($ampBuffer,"nbsp") == 0) $this->m_char = 160;
                    else if(strcmp($ampBuffer,"iexcl") == 0) $this->m_char = 161;
                    else if(strcmp($ampBuffer,"cent") == 0) $this->m_char = 162;
                    else if(strcmp($ampBuffer,"pound") == 0) $this->m_char = 163;
                    else if(strcmp($ampBuffer,"yen") == 0) $this->m_char = 165;
                    else if(strcmp($ampBuffer,"sect") == 0) $this->m_char = 167;
                    else if(strcmp($ampBuffer,"copy") == 0) $this->m_char = 169;
                    else if(strcmp($ampBuffer,"laquo ") == 0) $this->m_char = 171;
                    else if(strcmp($ampBuffer,"reg") == 0) $this->m_char = 174;
                    else if(strcmp($ampBuffer,"plusmn") == 0) $this->m_char = 177;
                    else if(strcmp($ampBuffer,"micro") == 0) $this->m_char = 181;
                    else if(strcmp($ampBuffer,"para") == 0) $this->m_char = 182;
                    else if(strcmp($ampBuffer,"middot") == 0) $this->m_char = 183;
                    else if(strcmp($ampBuffer,"raquo") == 0) $this->m_char = 187;
                    else if(strcmp($ampBuffer,"iquest") == 0) $this->m_char = 191;
                    else if(strcmp($ampBuffer,"Agrave") == 0) $this->m_char = 192;
                    else if(strcmp($ampBuffer,"Aacute") == 0) $this->m_char = 193;
                    else if(strcmp($ampBuffer,"Acirc") == 0) $this->m_char = 194;
                    else if(strcmp($ampBuffer,"Atilde") == 0) $this->m_char = 195;
                    else if(strcmp($ampBuffer,"Auml") == 0) $this->m_char = 196;
                    else if(strcmp($ampBuffer,"Aring") == 0) $this->m_char = 197;
                    else if(strcmp($ampBuffer,"AElig") == 0) $this->m_char = 198;
                    else if(strcmp($ampBuffer,"Ccedil") == 0) $this->m_char = 199;
                    else if(strcmp($ampBuffer,"Egrave") == 0) $this->m_char = 200;
                    else if(strcmp($ampBuffer,"Eacute") == 0) $this->m_char = 201;
                    else if(strcmp($ampBuffer,"Ecirc") == 0) $this->m_char = 202;
                    else if(strcmp($ampBuffer,"Euml") == 0) $this->m_char = 203;
                    else if(strcmp($ampBuffer,"Igrave") == 0) $this->m_char = 204;
                    else if(strcmp($ampBuffer,"Iacute") == 0) $this->m_char = 205;
                    else if(strcmp($ampBuffer,"Icirc") == 0) $this->m_char = 206;
                    else if(strcmp($ampBuffer,"Iuml") == 0) $this->m_char = 207;
                    else if(strcmp($ampBuffer,"Ntilde") == 0) $this->m_char = 209;
                    else if(strcmp($ampBuffer,"Ograve") == 0) $this->m_char = 210;
                    else if(strcmp($ampBuffer,"Oacute") == 0) $this->m_char = 211;
                    else if(strcmp($ampBuffer,"Ocirc") == 0) $this->m_char = 212;
                    else if(strcmp($ampBuffer,"Otilde") == 0) $this->m_char = 213;
                    else if(strcmp($ampBuffer,"Ouml") == 0) $this->m_char = 214;
                    else if(strcmp($ampBuffer,"Oslash") == 0) $this->m_char = 216;
                    else if(strcmp($ampBuffer,"Ugrave") == 0) $this->m_char = 217;
                    else if(strcmp($ampBuffer,"Uacute") == 0) $this->m_char = 218;
                    else if(strcmp($ampBuffer,"Ucirc") == 0) $this->m_char = 219;
                    else if(strcmp($ampBuffer,"Uuml") == 0) $this->m_char = 220;
                    else if(strcmp($ampBuffer,"szlig") == 0) $this->m_char = 223;
                    else if(strcmp($ampBuffer,"agrave ") == 0) $this->m_char = 224;
                    else if(strcmp($ampBuffer,"aacute ") == 0) $this->m_char = 225;
                    else if(strcmp($ampBuffer,"acirc ") == 0) $this->m_char = 226;
                    else if(strcmp($ampBuffer,"atilde ") == 0) $this->m_char = 227;
                    else if(strcmp($ampBuffer,"auml ") == 0) $this->m_char = 228;
                    else if(strcmp($ampBuffer,"aring ") == 0) $this->m_char = 229;
                    else if(strcmp($ampBuffer,"aelig ") == 0) $this->m_char = 230;
                    else if(strcmp($ampBuffer,"ccedil ") == 0) $this->m_char = 231;
                    else if(strcmp($ampBuffer,"egrave ") == 0) $this->m_char = 232;
                    else if(strcmp($ampBuffer,"eacute ") == 0) $this->m_char = 233;
                    else if(strcmp($ampBuffer,"ecirc ") == 0) $this->m_char = 234;
                    else if(strcmp($ampBuffer,"euml ") == 0) $this->m_char = 235;
                    else if(strcmp($ampBuffer,"igrave ") == 0) $this->m_char = 236;
                    else if(strcmp($ampBuffer,"iacute ") == 0) $this->m_char = 237;
                    else if(strcmp($ampBuffer,"icirc ") == 0) $this->m_char = 238;
                    else if(strcmp($ampBuffer,"iuml ") == 0) $this->m_char = 239;
                    else if(strcmp($ampBuffer,"ntilde ") == 0) $this->m_char = 241;
                    else if(strcmp($ampBuffer,"ograve ") == 0) $this->m_char = 242;
                    else if(strcmp($ampBuffer,"oacute ") == 0) $this->m_char = 243;
                    else if(strcmp($ampBuffer,"ocirc ") == 0) $this->m_char = 244;
                    else if(strcmp($ampBuffer,"otilde ") == 0) $this->m_char = 245;
                    else if(strcmp($ampBuffer,"ouml ") == 0) $this->m_char = 246;
                    else if(strcmp($ampBuffer,"divide") == 0) $this->m_char = 247;
                    else if(strcmp($ampBuffer,"oslash ") == 0) $this->m_char = 248;
                    else if(strcmp($ampBuffer,"ugrave ") == 0) $this->m_char = 249;
                    else if(strcmp($ampBuffer,"uacute ") == 0) $this->m_char = 250;
                    else if(strcmp($ampBuffer,"ucirc ") == 0) $this->m_char = 251;
                    else if(strcmp($ampBuffer,"uuml ") == 0) $this->m_char = 252;
                    else if(strcmp($ampBuffer,"yuml") == 0) $this->m_char = 255;
                    else if(strcmp($ampBuffer,"quot") == 0) $this->m_char = 34;
                    else if(strcmp($ampBuffer,"amp") == 0) $this->m_char = 38;
                    else if(strcmp($ampBuffer,"lt") == 0) $this->m_char = 60;
                    else if(strcmp($ampBuffer,"gt") == 0) $this->m_char = 62;
                    else if(strcmp($ampBuffer,"ndash") == 0) $this->m_char = 8211;
                    else if(strcmp($ampBuffer,"mdash") == 0) $this->m_char = 8212;
                    else if(strcmp($ampBuffer,"lsquo") == 0) $this->m_char = 8216;
                    else if(strcmp($ampBuffer,"rsquo") == 0) $this->m_char = 8217;
                    else if(strcmp($ampBuffer,"ldquo") == 0) $this->m_char = 8220;
                    else if(strcmp($ampBuffer,"rdquo") == 0) $this->m_char = 8221;
                    else if(strcmp($ampBuffer,"euro") == 0) $this->m_char = 8364;
                    else continue; // if we couldn't figure out what this character is, skip it

                    if($wordLength < WORDMAXIMUMLENGTH)
                    {
                        $word[$wordLength]=$this->m_char; // add this character to the $word
                        $wordLength++;
                        $this->m_gotWord=true;
                    }
                }
                else if($wordLength < WORDMAXIMUMLENGTH)
                {
                    $word[$wordLength]=$this->m_char; // add this character to the $word
                    $wordLength++;
                    $this->m_gotWord=true;
                }
            }
        }
        else if( $this->m_contentType == "CONTENT_TYPE_TXT" )
        {            
            while(true)
            {
                if($this->m_gotChar) $this->m_gotChar = false;  // check to see if we already have the next character
                else $this->m_char=$this->GetCharTxt();         // otherwise, get the next character (normal or UTF-8)

                if($this->m_char < 0 || $this->m_char == '' ) // check for EOF encountered 
                {
                    $delimiterType = DEL_TYPE_EOF;
                    return -1;
                }
                else if(sizeof(preg_split('/\r\n|\r|\n/',$this->m_char)) >1  || ($this->m_char == '\n') || ($this->m_char == '\r') ) // check for newline characters
                {
                    $delimiterType=DEL_TYPE_NEWLINE;
                    $this->m_gotDelimiter=true;
                }
                else if(IntlChar::isspace($this->m_char)) // check for white space
                {
                    $delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
                    $this->m_gotDelimiter=true;
                }
                else if( (IntlChar::iscntrl($this->m_char) && ord($this->m_char ) < 0x80) || (ord($this->m_char) == 0xff) ) continue; // skip any other control characters
                else if($this->m_gotDelimiter) // have we just reached the end of one or more delimiters?
                {
                    if($this->m_gotWord) // make sure that we have a $word
                    {
                        // //$word[$wordLength]=0; // finish the word off
                        $this->m_gotChar=true;
                        return -1;
                    }
                    else // these were preliminary delimiters and we will ignore them
                    {
                        $delimiterType=DEL_TYPE_NONE;
                        $this->m_gotDelimiter=false;
                        $this->m_gotChar=true;
                    }
                }
                else if($wordLength < WORDMAXIMUMLENGTH)
                {
                    // echo ' Wordlength: '. $wordLength. ' Char in question:' . $this->m_char . ' Word in question:';
                    // var_dump($word );
                    // echo '\n';
                    $word[$wordLength]=$this->m_char; // add this character to the $word                    
                    $this->m_gotWord=true;
                    $wordLength++;
                }
            }
        }
        else if($this->m_contentType == "CONTENT_TYPE_DOC")
        {
            while(true)
            {
                if($this->m_gotChar) $this->m_gotChar = false; // check to see if we already have the next character
                else $this->m_char=fgetc($this->m_filep);
                
                if(ord($this->m_char )>= 0x80)	// convert extended ISO8559-1 characters into appropriate unicode characters
                {
                    switch( $this->m_char )
                    {
                    case 128: $this->m_char = 8364; break;
                    case 129: $this->m_char = 32; break;
                    case 130: $this->m_char = 8218; break;
                    case 131: $this->m_char = 402; break;
                    case 132: $this->m_char = 8222; break;
                    case 133: $this->m_char = 8230; break;
                    case 134: $this->m_char = 8224; break;
                    case 135: $this->m_char = 8225; break;
                    case 136: $this->m_char = 170; break;
                    case 137: $this->m_char = 8240; break;
                    case 138: $this->m_char = 353; break;
                    case 139: $this->m_char = 8249; break;
                    case 140: $this->m_char = 339; break;
                    case 141: $this->m_char = 32; break;
                    case 142: $this->m_char = 381; break;
                    case 143: $this->m_char = 32; break;
                    case 144: $this->m_char = 32; break;
                    case 145: $this->m_char = 8216; break;
                    case 146: $this->m_char = 8217; break;
                    case 147: $this->m_char = 8220; break;
                    case 148: $this->m_char = 8221; break;
                    case 149: $this->m_char = 8226; break;
                    case 150: $this->m_char = 8211; break;
                    case 151: $this->m_char = 8212; break;
                    case 152: $this->m_char = 732; break;
                    case 153: $this->m_char = 8482; break;
                    case 154: $this->m_char = 353; break;
                    case 155: $this->m_char = 8250; break;
                    case 156: $this->m_char = 339; break;
                    case 157: $this->m_char = 32; break;
                    case 158: $this->m_char = 382; break;
                    case 159: $this->m_char = 376; break;
                    }
                }
                else if($this->m_char< 0 || $this->m_char == '') // check for EOF encountered
                {
                    //$word[$wordLength]=0; // finish the $word off
                    $delimiterType = DEL_TYPE_EOF;
                    return -1;
                }
                else if( sizeof(preg_split('/\r\n|\r|\n/',$this->m_char)) >1 ||($this->m_char == '\n') || ($this->m_char == '\r')) // check for newline characters
                {
                    $delimiterType=DEL_TYPE_NEWLINE;
                    $this->m_gotDelimiter=true;
                }
                else if( ($this->m_char == '\t') || ($this->m_char == ' ')) // check for tab or space characters
                {
                    $delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
                    $this->m_gotDelimiter=true;
                }
                else if ( IntlChar::iscntrl($this->m_char) && (ord($this->m_char )< 0x80) ) // if we encounter a control character, restart the $word search
                {
                    //$word[0]=0; // start with empty $word, in case we encounter EOF before we encounter a $word
                    $wordLength = 0;
                    $this->m_gotWord = false;
                    $this->m_gotDelimiter = false;
                    $delimiterType = DEL_TYPE_NONE;
                }
                else if($this->m_bBasic_Characters && (ord($this->m_char )>= 0x80 )) // if we're using basic characters only and this is a non-basic character, restart the $word search
                {
                    //$word[0]=0; // start with empty $word, in case we encounter EOF before we encounter a $word
                    $wordLength = 0;
                    $this->m_gotWord = false;
                    $this->m_gotDelimiter = false;
                    $delimiterType = DEL_TYPE_NONE;
                }
                else if($this->m_gotDelimiter) // have we just reached the end of one or more delimiters?
                {
                    if($this->m_gotWord) // make sure that we have a $word
                    {
                        //$word[$wordLength]=0; // finish the $word off
                        $this->m_gotChar=true;
                        return -1;
                    }
                    else // these were preliminary delimiters and we will ignore them
                    {
                        $delimiterType=DEL_TYPE_NONE;
                        $this->m_gotDelimiter=false;
                        $this->m_gotChar=true;
                    }
                }
                else if( $wordLength < WORDMAXIMUMLENGTH )
                {
                    $word[$wordLength]=$this->m_char; // add this character to the $word
                    $wordLength++;
                    $this->m_gotWord=true;
                }
		    }
        }
        else if( $this->m_contentType == "CONTENT_TYPE_PDF" )
        {
            while(true)
            {
                if($this->m_gotChar) $this->m_gotChar = false; // check to see if we already have the next character
                else
                {
                    $this->m_char=$this->GetCharPdf();
                }
                if($this->m_char < 0  ) // check for EOF encountered
                {
                    // $word[$wordLength]='0'; // finish the $word off
                    $delimiterType = DEL_TYPE_EOF;
                    return -1;
                }
                else if( sizeof(preg_split('/\r\n|\r|\n/',$this->m_char)) >1 || ($this->m_char == '\n') || ($this->m_char == '\r') ) // check for newline characters
                {
                    $delimiterType=DEL_TYPE_NEWLINE;
                    $this->m_gotDelimiter=true;
                }
                else if(IntlChar::isspace($this->m_char)) // check for white space
                {
                    $delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
                    $this->m_gotDelimiter=true;
                }
                else if( (IntlChar::iscntrl(ord($this->m_char)) && ord($this->m_char) < 0x80) || (ord($this->m_char) == 0xff) ) continue; // skip any other control characters
                else if($this->m_gotDelimiter) // have we just reached the end of one or more delimiters?
                {
                    if($this->m_gotWord) // make sure that we have a $word
                    {
                        // $word[$wordLength]='0'; // finish the $word off
                        $this->m_gotChar=true;
                        return -1;
                    }
                    else // these were preliminary delimiters and we will ignore them
                    {
                        $delimiterType=DEL_TYPE_NONE;
                        $this->m_gotDelimiter=false;
                        $this->m_gotChar=true;
                    }
                }
                else if( (ord($this->m_char) >= 0xfb00) && (ord($this->m_char) <= 0xfb06) )	// check for ligatured charactures
                {
                    if($wordLength < WORDMAXIMUMLENGTH-3)
                    {
                        switch( $this->m_char )
                        {
                        case 0xfb00:
                                $word[$wordLength]='f';
                                $wordLength++;
                                $word[$wordLength]='f';
                                $wordLength++;
                                break;
                        case 0xfb01:
                                $word[$wordLength]='f';
                                $wordLength++;
                                $word[$wordLength]='i';
                                $wordLength++;
                                break;
                        case 0xfb02:
                                $word[$wordLength]='f';
                                $wordLength++;
                                $word[$wordLength]='l';
                                $wordLength++;
                                break;
                        case 0xfb03:
                                $word[$wordLength]='f';
                                $wordLength++;
                                $word[$wordLength]='f';
                                $wordLength++;
                                $word[$wordLength]='i';
                                $wordLength++;
                                break;
                        case 0xfb04:
                                $word[$wordLength]='f';
                                $wordLength++;
                                $word[$wordLength]='f';
                                $wordLength++;
                                $word[$wordLength]='l';
                                $wordLength++;
                                break;
                        case 0xfb05:
                                $word[$wordLength]='f';
                                $wordLength++;
                                $word[$wordLength]='t';
                                $wordLength++;
                                break;
                        case 0xfb06:
                                $word[$wordLength]='s';
                                $wordLength++;
                                $word[$wordLength]='t';
                                $wordLength++;
                                break;
                        }
                        $this->m_gotWord=true;
                    }
                }
                else if($wordLength < WORDMAXIMUMLENGTH)
                {
                    $word[$wordLength]=$this->m_char; // add this character to the $word
                    $wordLength++;
                    $this->m_gotWord=true;
                }
            }
            }
            else // unknown type, so read $bytes
            {
                while(true)
                {
                    if($this->m_gotChar) $this->m_gotChar = false; // check to see if we already have the next character
                    else $this->m_char=fgetc($this->m_filep);
                    
                    if($this->m_char == 0) $this->m_char=fgetc( $this->m_filep); // skip a single null character (but not multiple nulls)
                    //|| $this->m_char == ''
                    if($this->m_char < 0 || $this->m_char == false ) // check for EOF encountered
                    {
                        $word[$wordLength]=0; // finish the $word off
                        $delimiterType = DEL_TYPE_EOF;
                        return -1;
                    }
                    else if( ($this->m_char == '\n') || ($this->m_char == '\r')) // check for newline characters
                    {
                        $delimiterType=DEL_TYPE_NEWLINE;
                        $this->m_gotDelimiter=true;
                    }
                    else if( ($this->m_char == '\t') || ($this->m_char == ' ')) // check for tab or space characters
                    {
                        $delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
                        $this->m_gotDelimiter=true;
                    }
                    else if ( (IntlChar::iscntrl($this->m_char) && ord($this->m_char ) < 0x80) || ($this->m_char == 0xff) ) // if we encounter a control character, restart the $word search
                    {
                        $word[0]=0; // start with empty $word, in case we encounter EOF before we encounter a $word
                        $word[$wordLength]=0; // finish the $word off
                        $wordLength = 0;
                        $this->m_gotWord = false;
                        $this->m_gotDelimiter = false;
                        $delimiterType = DEL_TYPE_NONE;
                    }
                    else if($this->m_gotDelimiter) // have we just reached the end of one or more delimiters?
                    {
                        if($this->m_gotWord) // make sure that we have a $word
                        {
                            // //$word[$wordLength]=0; // finish the $word off
                            $word[$wordLength]=0; // finish the $word off
                            $this->m_gotChar=true;
                            return -1;
                        }
                        else // these were preliminary delimiters and we will ignore them
                        {
                            $delimiterType=DEL_TYPE_NONE;
                            $this->m_gotDelimiter=false;
                            $this->m_gotChar=true;
                        }
                    }
                    else if( $wordLength < WORDMAXIMUMLENGTH )
                    {
                        $word[$wordLength]=$this->m_char; // add this character to the $word
                        $wordLength++;
                        $this->m_gotWord=true;
                    }
                }	    
        }
	    return -1;
    }

    //Char functions

    function GetCharTxt()
    {
    
        if(!$this->m_UTF8) return $this->GetByteTxt();
    
        while(true)
        {
            $thisChar = $this->GetByteTxt();
            if($thisChar < 0) return -1; // end of file reached?
            else if((ord($thisChar) & 0x80) == 0) // one-byte character?
            {
                return $thisChar;
            }
            else if((ord($thisChar) & 0x20) == 0) // two-byte character?
            {
                $bytes=2;
                $thisChar = (ord($thisChar) & 0x1F);
            }
            else if((ord($thisChar) & 0x10) == 0) // three-byte character?
            {
                $bytes=3;
                $thisChar = (ord($thisChar) & 0x0F);
            }
            else if((ord($thisChar) & 0x08) == 0) // four-byte character?
            {
                $bytes=4;
                $thisChar = (ord($thisChar) & 0x07);
            }
            else return -1; // either bad unicode or character is more than four $bytes long
            for($i=1;$i<$bytes;$i++)
            {
                $thisByte=$this->GetByteTxt();
                if($thisByte < 0) return -1; // end of file reached prematurely?
                $thisChar = ($thisChar << 6) | (ord($thisByte) & 0x3F); // incorporate additional 6 bits
            }
            return $thisChar;
        }
    }
    
    function GetCharHtml()
    {    
        if(!$this->m_UTF8) return $this->GetByteHtml();
    
        while(true)
        {
            $thisChar = $this->GetByteHtml();
            if($thisChar < 0) return -1; // end of file reached?
            else if((ord($thisChar) & 0x80) == 0) // one-byte character?
            {
                return $thisChar;
            }
            else if((ord($thisChar) & 0x20) == 0) // two-byte character?
            {
                $bytes=2;
                $thisChar = (ord($thisChar) & 0x1F);
            }
            else if((ord($thisChar) & 0x10) == 0) // three-byte character?
            {
                $bytes=3;
                $thisChar = (ord($thisChar) & 0x0F);
            }
            else if((ord($thisChar) & 0x08) == 0) // four-byte character?
            {
                $bytes=4;
                $thisChar = (ord($thisChar) & 0x07);
            }
            else return -1; // either bad unicode or character is more than four $bytes long
            for($i=1;$i<$bytes;$i++)
            {
                $thisByte=$this->GetByteHtml();
                if($thisByte < 0) return -1; // end of file reached prematurely?
                $thisChar = ($thisChar << 6) | (ord($thisByte) & 0x3F); // incorporate additional 6 bits
            }
            return $thisChar;
        }
    }
    
    function GetCharPdf()
    {
        if($this->m_pdftotext)  // did pdftotext do the conversion to a UTF-8 piped input file?
        {
    
            if(!$this->m_UTF8) return $this->GetByteHtml(); // will never happen, but included for completeness
    
            while(true)
            {
                $thisChar = $this->GetBytePdf();
                //if(!is_numeric($thisChar) )echo "Hier:". $thisChar;
                if(ord($thisChar) < 0 || $thisChar == false || $thisChar == '' ) return -1; // end of file reached?
                else if((ord($thisChar) & 0x80) == 0) // one-byte character?
                {
                    return $thisChar;
                }
                else if((ord($thisChar) & 0x20) == 0) // two-byte character?
                {
                    $bytes=2;
                    $thisChar = (ord($thisChar) & 0x1F);
                }
                else if((ord($thisChar) & 0x10) == 0) // three-byte character?
                {
                    $bytes=3;
                    $thisChar = (ord($thisChar) & 0x0F);
                }
                else if((ord($thisChar) & 0x08) == 0) // four-byte character?
                {
                    $bytes=4;
                    $thisChar = (ord($thisChar) & 0x07);
                }
                else return -1; // either bad unicode or character is more than four $bytes long
                for($i=1;$i<$bytes;$i++)
                {
                    $thisByte=$this->GetBytePdf();
                    if(ord($thisByte) < 0) return -1; // end of file reached prematurely?
                    $thisChar = ($thisChar << 6) | (ord($thisByte) & 0x3F); // incorporate additional 6 bits
                }
                return $thisChar;
            }
        }
        else // no pdftotext, so we're using the simple conversion and reading characters from a memory buffer
        {
            if( $this->m_BytePointerPdf < $this->m_ByteCountPdf )
            {
                $this->m_BytePointerPdf++;
                return $this->m_ByteBufferPdf[$this->m_BytePointerPdf-1];
            }
            else return -1;
        }
    }

    function GetCharDocx()
    {

        if(!$this->m_UTF8) return $this->GetByteDocx(); // will never happen, but included for completeness
        while(true)
        {
            $thisChar = $this->GetByteDocx();
            if($thisChar < 0) return -1; // end of file reached?
            else if((ord($thisChar) & 0x80) == 0) // one-byte character?
            {
                return $thisChar;
            }
            else if((ord($thisChar) & 0x20) == 0) // two-byte character?
            {
                $bytes=2;
                $thisChar = (ord($thisChar) & 0x1F);
            }
            else if((ord($thisChar) & 0x10) == 0) // three-byte character?
            {
                $bytes=3;
                $thisChar = (ord($thisChar) & 0x0F);
            }
            else if((ord($thisChar) & 0x08) == 0) // four-byte character?
            {
                $bytes=4;
                $thisChar = (ord($thisChar) & 0x07);
            }
            else return -1; // either bad unicode or character is more than four $bytes long
            for($i=1;$i<$bytes;$i++)
            {
                $thisByte=$this->GetByteDocx();
                if($thisByte < 0) return -1; // end of file reached prematurely?
                $thisChar = ($thisChar << 6) | (ord($thisByte) & 0x3F); // incorporate additional 6 bits
            }
            return $thisChar;
        }
    }

    //Byte functions

    function GetByteDocx()
    {
        while(true)
        {
            if($this->m_ByteIndexDocx < $this->m_ByteCountDocx)
            {
                //where do we get the buffer from? 
                $thisByte=$this->m_docxByteBuffer[$this->m_ByteIndexDocx];
                $this->m_ByteIndexDocx++;
                return $thisByte;
            }
            else if($this->m_eof) return -1;
            else
            {
                //find replacement
                // $zip = new ZipArchive();
                // $this->m_ByteCountDocx = $zip->open($this->m_docxZipArchive);
                // $this->m_ByteCountDocx = unzReadCurrentFile($m_docxZipArchive,$m_docxByteBuffer,$m_docxByteBufferLength);
                $this->m_ByteIndexDocx = 0;
                if($this->m_ByteCountDocx==0)
                {
                    $this->m_eof=true;
                    return -1;
                }
            }
        }
    }

    function GetByteTxt()
    {
        if($this->m_bInternet)
        {
            $byte = $this->m_pHttpFile->read(1);
            if( $byte >0) return $byte;
            else return -1;
        }
        return fgetc($this->m_filep);
    }

    function GetByteHtml()
    {
        if($this->m_bInternet)
        {
            $byte = $this->m_pHttpFile->read(1);
            if($byte > 0) return $byte;
            else return -1;
        }
        else return fgetc($this->m_filep);
    }

    function GetBytePdf()
    {
        return fgetc($this->m_filep);
    }



}