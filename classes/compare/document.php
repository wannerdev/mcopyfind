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
    
	public $ $m_gotword = false;
	public $m_gotDelimiter = false;

    public $m_pWordHash = []; // hash of unsorted words in document 
    public $pSortedWordHash = []; // a pointer to the hash-coded word list
    public $pSortedWordNumber = []; // a pointer to the sorted hash-coded word list
    public $m_WordsTotal = 0; // an entry for the number of $words in the lists
    public $firstHash = -1; // an entry for the first word with more than 3 chars
    public $m_bBasic_Characters; // how to open it (UTF-8 or not)?

    public $file = null;
    public $filename = null;

    // public $wordNumber = 0;
    public $realwords = 0;
    public $words =null;
    public $ $this->m_filep=null;
    public $m_fHtml=null;

    public function __construct()
    {
        $this->words = new words();
    }

    function definePath($infile){
        if($infile==null)
            return;
        $this->filename = $infile;
        $this->OpenDocument();
    }

    function OpenDocument(){
        $pfilename = $this->filename;
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
        $this-> $this->m_filep = fopen($filename,"r");
        if($this-> $this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->$this->m_haveFile = true;
        $this->m_UTF8 = true; // assume that the html file is encoded in UTF-8
        return -1;
    }

    function OpenDoc($filename)
    {
        $this-> $this->m_filep = fopen($filename,"rb"); // open in binary read mode
        if($this-> $this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        $this->m_UTF8 = false;
        return -1;
    }

    function OpenTxt($filename)
    {
        $this-> $this->m_filep = fopen($filename,"r");
        if($this-> $this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
        $this->m_haveFile = true;
        if(fgetc($this-> $this->m_filep) == 0xEF) // check for the BOM to indicate a utf-8 text file
        {
            if(fgetc($this-> $this->m_filep) == 0xBB)
            {
                if(fgetc($this-> $this->m_filep) == 0xBF)
                {
                    $this->m_UTF8 = true;  // found BOM; leave input pointing after the BOM
                    return -1;
                }
            }
        }
        $this->m_UTF8 = false;
        fseek($this-> $this->m_filep,0,SEEK_SET); // not a utf-8 text file, so rewind to the beginning
        return -1;
    }

    //todo translate fully to php
     function OpenUrl($filename)
    {
        $this-> $this->m_filep = fopen($filename,"r");
        if($this-> $this->m_filep == NULL) throw new Exception("ERR_CANNOT_FIND_URL_LINK");
    
        $string=true;
        // $szmessage;
    
        while($string)
        {
            $string=stream_get_contents($this-> $this->m_filep, -1, 255);
            if(strcmp($string[0],"url=",4) == 0)
            {
                fclose($this-> $this->m_filep); $this-> $this->m_filep = NULL;
                $dwHttpRequestFlags = "INTERNET_FLAG_EXISTING_CONNECT";
                $szHeaders[] = "Accept: text/*\r\nUser-Agent: WCopyfind\r\n";
    
                // $dwServiceType;
                // $strServerName;
                // $strObject;
                // $nPort;
                //!AfxParseURL(, dwServiceType, strServerName, strObject, nPort) || dwServiceType != INTERNET_SERVICE_HTTP
                
                if (parse_url($string[4]))
                {
                    fclose($this-> $this->m_filep); $this-> $this->m_filep = NULL;
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
                //     fclose(this-> $this->m_filep); this-> $this->m_filep = NULL;
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
        $this-> $this->m_filep=fopen($filename,"r");
        if($this-> $this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE");
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
        //     $this-> $this->m_filep = NULL;
        //     throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE";
        // }

        // $rv = unzLocateFile(m_docxZipArchive, "word/document.xml", NULL);
        // if(rv != UNZ_OK)
        // {
        //     unzClose(m_docxZipArchive);
        //     m_docxZipArchive=NULL;
        //     $this-> $this->m_filep = NULL;
        //     return ERR_BAD_DOCX_FILE;
        // }
        // unzOpenCurrentFile(m_docxZipArchive);
        // $this->m_haveFile = true;
        // $m_UTF8 = true;
        // m_ByteIndexDocx=0; // start at first byte
        // m_ByteCountDocx=0; // there are currently zero bytes
        // $this-> $this->m_filep = 1; // indicate that the file was found?
        return -1;
    }

    function OpenPdf($filename)
    { 
        $this-> $this->m_filep = fstat($filename,"rb");
        if($this-> $this->m_filep == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE"); // open fails if file didn't actually open
        if($_FILES['file']['type']=="application/pdf") {
            $a = new PDF2Text();
            $a->setFilename($filename);
            $this->m_filep =$a->decodePDF();

            $this->m_haveFile = true;
            $this->m_UTF8 = true;

            return $a->output();
        }
        
        /**
         *       if($m_pdftotext) // pdftotext program exists, so use it to pipe text to this program
         *    assemble command line, e.g.: ""C:\\fullpath\\pdftotext.exe" -enc UTF-8 "C:\\anotherpath\\xin.pdf" - "
         *   // where the final '-' indicates that the output should be piped to a readable input pipe
         *   // both $filenames (pdftotext.exe and xxx.pdf) need to be in quotes, in case they contain spaces, and the entire command must also be in quotes.
         *   $commandLine="\"\"". $m_pdftotextFile  . "\"\" -enc UTF-8 \"" . $filename . "\" - \""; // assemble command 
         *
         *   if(($this->m_filep = _wpopen($commandLine,"r")) == NULL) throw new Exception("ERR_CANNOT_OPEN_INPUT_FILE"; // if the pipe don't form, command filed.
         *   $this->m_haveFile = true;
         *   $this->m_UTF8 = true;
         *
         *   return -1; // we should now have the text portion of the PDF file as a readable file attached to $this-> $this->m_filep
         *}
         * else // don't have pdftotext program avialable, so use default pdf function
         */
    }
    
    function Getword($word,$delimiterType)
{
	if(!$m_haveFile) return ERR_NO_FILE_OPEN; // if no file is open, failure

	$word[0]=0; // start with empty $word, in case we encounter EOF before we encounter a $word
	$wordLength = 0;
	$this-> $m_gotword = false;
	$this->$m_gotDelimiter = false;
	$delimiterType = DEL_TYPE_NONE;

	if($this->m_contentType == CONTENT_TYPE_DOCX)
	{
		while(true)
		{
			if($m_gotChar) $m_gotChar = false; // check to see if we already have the next character
			else $m_char=GetCharDocx();
			
			if($m_char < 0) // check for EOF encountered
			{
				$word[$wordLength]=0; // finish the $word off
				$delimiterType = DEL_TYPE_EOF;
				return -1;
			}
			else if($m_char == '<') // check for '<' character
			{
				$tagName = "";
				$bneedtagName = true;

				while(true) // read in the entire tag, saving the tag name
				{
					$m_char=GetCharDocx();
					if($m_char < 0) // check for EOF encountered
					{
						$word[$wordLength]=0; // finsh the $word off;
						$delimiterType = DEL_TYPE_EOF;
						return -1;
					}
					else if($m_char == '>') break; // found '>' (end of tag), so stop scanning it
					else if($m_char == ' ') $bneedtagName = false; // found ' ' (end of tag name), so stop gathering name
					else if($bneedtagName) $tagName += $m_char;
				}
				if( $tagName.CompareNoCase("w:p" ) == 0) // check for a paragraph tag
				{
					$delimiterType=DEL_TYPE_NEWLINE;
					$m_gotDelimiter=true;
				}
				else if( $tagName.CompareNoCase("w:tab/" ) == 0) // check for a tab tag
				{
					$delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
					$m_gotDelimiter=true;
				}
				continue; // otherwise keep reading
			}
			else if(iswspace($m_char)) // check for white space
			{
				$delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
				$m_gotDelimiter=true;
			}
			else if(iswcntrl($m_char)) continue; // skip other control characters
			else if($m_gotDelimiter) // have we just reached the end of one or more delimiters?
			{
				if($ $m_gotword) // make sure that we have a $word
				{
					$word[$wordLength]=0; // finish the $word off
					$m_gotChar=true;
					return -1;
				}
				else // these were preliminary delimiters and we will ignore them
				{
					$delimiterType=DEL_TYPE_NONE;
					$m_gotDelimiter=false;
					$m_gotChar=true;
				}
			}
			else if($m_char == '&') // skip and ignore &xxx; codes.
			{
				$ampBuffer[256];
				$ampBufferCount = 0;
				while(true)
				{
					$m_char=GetCharDocx(); // read the next character
					if( (!iswalnum($m_char)) && ($m_char != '#')) break; // keep reading until we hit a non-alphanumeric, which should be a ';')
					if ($m_char < 0) break; // we encountered an EOF before the end of the & code
					if($ampBufferCount < 255)
					{
						$ampBuffer[$ampBufferCount] = $m_char;
						$ampBufferCount++;
					}
				}
				$ampBuffer[$ampBufferCount] = 0; // finish off the string
				if($ampBuffer[0] == '#')
				{
					if($ampBuffer[1] == 'x') swscanf_s($ampBuffer+2,L"%x",&$m_char); // read in the hexidecial number directly
					else swscanf_s($ampBuffer+1,L"%d",&$m_char); // read in the character number directly
				}

				else if(wcscmp($ampBuffer,L"trade") == 0) $m_char = 153;
				else if(wcscmp($ampBuffer,L"nbsp") == 0) $m_char = 160;
				else if(wcscmp($ampBuffer,L"iexcl") == 0) $m_char = 161;
				else if(wcscmp($ampBuffer,L"cent") == 0) $m_char = 162;
				else if(wcscmp($ampBuffer,L"pound") == 0) $m_char = 163;
				else if(wcscmp($ampBuffer,L"yen") == 0) $m_char = 165;
				else if(wcscmp($ampBuffer,L"sect") == 0) $m_char = 167;
				else if(wcscmp($ampBuffer,L"copy") == 0) $m_char = 169;
				else if(wcscmp($ampBuffer,L"laquo ") == 0) $m_char = 171;
				else if(wcscmp($ampBuffer,L"reg") == 0) $m_char = 174;
				else if(wcscmp($ampBuffer,L"plusmn") == 0) $m_char = 177;
				else if(wcscmp($ampBuffer,L"micro") == 0) $m_char = 181;
				else if(wcscmp($ampBuffer,L"para") == 0) $m_char = 182;
				else if(wcscmp($ampBuffer,L"middot") == 0) $m_char = 183;
				else if(wcscmp($ampBuffer,L"raquo") == 0) $m_char = 187;
				else if(wcscmp($ampBuffer,L"iquest") == 0) $m_char = 191;
				else if(wcscmp($ampBuffer,L"Agrave") == 0) $m_char = 192;
				else if(wcscmp($ampBuffer,L"Aacute") == 0) $m_char = 193;
				else if(wcscmp($ampBuffer,L"Acirc") == 0) $m_char = 194;
				else if(wcscmp($ampBuffer,L"Atilde") == 0) $m_char = 195;
				else if(wcscmp($ampBuffer,L"Auml") == 0) $m_char = 196;
				else if(wcscmp($ampBuffer,L"Aring") == 0) $m_char = 197;
				else if(wcscmp($ampBuffer,L"AElig") == 0) $m_char = 198;
				else if(wcscmp($ampBuffer,L"Ccedil") == 0) $m_char = 199;
				else if(wcscmp($ampBuffer,L"Egrave") == 0) $m_char = 200;
				else if(wcscmp($ampBuffer,L"Eacute") == 0) $m_char = 201;
				else if(wcscmp($ampBuffer,L"Ecirc") == 0) $m_char = 202;
				else if(wcscmp($ampBuffer,L"Euml") == 0) $m_char = 203;
				else if(wcscmp($ampBuffer,L"Igrave") == 0) $m_char = 204;
				else if(wcscmp($ampBuffer,L"Iacute") == 0) $m_char = 205;
				else if(wcscmp($ampBuffer,L"Icirc") == 0) $m_char = 206;
				else if(wcscmp($ampBuffer,L"Iuml") == 0) $m_char = 207;
				else if(wcscmp($ampBuffer,L"Ntilde") == 0) $m_char = 209;
				else if(wcscmp($ampBuffer,L"Ograve") == 0) $m_char = 210;
				else if(wcscmp($ampBuffer,L"Oacute") == 0) $m_char = 211;
				else if(wcscmp($ampBuffer,L"Ocirc") == 0) $m_char = 212;
				else if(wcscmp($ampBuffer,L"Otilde") == 0) $m_char = 213;
				else if(wcscmp($ampBuffer,L"Ouml") == 0) $m_char = 214;
				else if(wcscmp($ampBuffer,L"Oslash") == 0) $m_char = 216;
				else if(wcscmp($ampBuffer,L"Ugrave") == 0) $m_char = 217;
				else if(wcscmp($ampBuffer,L"Uacute") == 0) $m_char = 218;
				else if(wcscmp($ampBuffer,L"Ucirc") == 0) $m_char = 219;
				else if(wcscmp($ampBuffer,L"Uuml") == 0) $m_char = 220;
				else if(wcscmp($ampBuffer,L"szlig") == 0) $m_char = 223;
				else if(wcscmp($ampBuffer,L"agrave ") == 0) $m_char = 224;
				else if(wcscmp($ampBuffer,L"aacute ") == 0) $m_char = 225;
				else if(wcscmp($ampBuffer,L"acirc ") == 0) $m_char = 226;
				else if(wcscmp($ampBuffer,L"atilde ") == 0) $m_char = 227;
				else if(wcscmp($ampBuffer,L"auml ") == 0) $m_char = 228;
				else if(wcscmp($ampBuffer,L"aring ") == 0) $m_char = 229;
				else if(wcscmp($ampBuffer,L"aelig ") == 0) $m_char = 230;
				else if(wcscmp($ampBuffer,L"ccedil ") == 0) $m_char = 231;
				else if(wcscmp($ampBuffer,L"egrave ") == 0) $m_char = 232;
				else if(wcscmp($ampBuffer,L"eacute ") == 0) $m_char = 233;
				else if(wcscmp($ampBuffer,L"ecirc ") == 0) $m_char = 234;
				else if(wcscmp($ampBuffer,L"euml ") == 0) $m_char = 235;
				else if(wcscmp($ampBuffer,L"igrave ") == 0) $m_char = 236;
				else if(wcscmp($ampBuffer,L"iacute ") == 0) $m_char = 237;
				else if(wcscmp($ampBuffer,L"icirc ") == 0) $m_char = 238;
				else if(wcscmp($ampBuffer,L"iuml ") == 0) $m_char = 239;
				else if(wcscmp($ampBuffer,L"ntilde ") == 0) $m_char = 241;
				else if(wcscmp($ampBuffer,L"ograve ") == 0) $m_char = 242;
				else if(wcscmp($ampBuffer,L"oacute ") == 0) $m_char = 243;
				else if(wcscmp($ampBuffer,L"ocirc ") == 0) $m_char = 244;
				else if(wcscmp($ampBuffer,L"otilde ") == 0) $m_char = 245;
				else if(wcscmp($ampBuffer,L"ouml ") == 0) $m_char = 246;
				else if(wcscmp($ampBuffer,L"divide") == 0) $m_char = 247;
				else if(wcscmp($ampBuffer,L"oslash ") == 0) $m_char = 248;
				else if(wcscmp($ampBuffer,L"ugrave ") == 0) $m_char = 249;
				else if(wcscmp($ampBuffer,L"uacute ") == 0) $m_char = 250;
				else if(wcscmp($ampBuffer,L"ucirc ") == 0) $m_char = 251;
				else if(wcscmp($ampBuffer,L"uuml ") == 0) $m_char = 252;
				else if(wcscmp($ampBuffer,L"yuml") == 0) $m_char = 255;
				else if(wcscmp($ampBuffer,L"quot") == 0) $m_char = 34;
				else if(wcscmp($ampBuffer,L"amp") == 0) $m_char = 38;
				else if(wcscmp($ampBuffer,L"lt") == 0) $m_char = 60;
				else if(wcscmp($ampBuffer,L"gt") == 0) $m_char = 62;
				else if(wcscmp($ampBuffer,L"ndash") == 0) $m_char = 8211;
				else if(wcscmp($ampBuffer,L"mdash") == 0) $m_char = 8212;
				else if(wcscmp($ampBuffer,L"lsquo") == 0) $m_char = 8216;
				else if(wcscmp($ampBuffer,L"rsquo") == 0) $m_char = 8217;
				else if(wcscmp($ampBuffer,L"ldquo") == 0) $m_char = 8220;
				else if(wcscmp($ampBuffer,L"rdquo") == 0) $m_char = 8221;
				else if(wcscmp($ampBuffer,L"euro") == 0) $m_char = 8364;
				else continue; // if we couldn't figure out what this character is, skip it

				if($wordLength < $wordMAXIMUMLENGTH)
				{
					$word[$wordLength]=$m_char; // add this character to the $word
					$wordLength++;
					$m_gotword=true;
				}
			}			
			else if($wordLength < $wordMAXIMUMLENGTH)
			{
				$word[$wordLength]=$m_char; // add this character to the $word
				$wordLength++;
				$m_gotword=true;
			}
		}
	}

	else if($this->m_contentType == CONTENT_TYPE_HTML)
	{
		$binScript = false;
		$binStyle = false;
		$binHeader = false;
		
		while(true)
		{
			if($m_gotChar) $m_gotChar = false; // check to see if we already have the next character
			else
			{
				$m_char=GetCharHtml(); // get a character
			}

			if($m_char < 0) // check for EOF encountered
			{
				$word[$wordLength]=0; // finish the $word off
				$delimiterType = DEL_TYPE_EOF;
				return -1;
			}
			if($m_char == '<') // check for "<" character
			{
				$tagName = "";
				$bneedtagName = true;
				$tagBody = "";

				while(true) // read in the entire tag, saving the tag name
				{
					$m_char=GetCharHtml();
					if($m_char < 0) // check for EOF encountered
					{
						$word[$wordLength]=0; // finsh the $word off;
						$delimiterType = DEL_TYPE_EOF;
						return -1;
					}
					else if($m_char == '>') break; // found ">" (end of tag), so stop scanning it
					else if($m_char == ' ') $bneedtagName = false; // found " " (end of tag name), so stop gathering name
					else if($bneedtagName) $tagName +=  $m_char; // save tag name
					else $tagBody +=  $m_char; // save tag body
					if( $tagName.Compare("!--") == 0 ) // are we starting a comment?
					{
						$dashes=0;
						while(true)
						{
							$m_char=GetCharHtml(); // read the next character
							if( ($m_char == '>') && ($dashes > 1) ) break; // we found the end of the comment
							if( $m_char < 0) break; // file ended without a completion of the comment
							else if( $m_char == '-') $dashes++; // we found a dash, so increment the count
							else $dashes = 0; // not a dash, so return the dash count to zero
						}
						break;
					}
				}
				if( ( $tagName.CompareNoCase(L"P") == 0 ) || // look for any tag that should trigger a new line
					( $tagName.CompareNoCase(L"BR") == 0 ) ||
					( $tagName.CompareNoCase(L"BR/") == 0 ) ||
					( $tagName.CompareNoCase(L"UL") == 0 ) ||
					( $tagName.CompareNoCase(L"TD") == 0 ) )
				{
					$delimiterType=DEL_TYPE_NEWLINE;
					$m_gotDelimiter=true;
				}
				else if( $tagName.CompareNoCase(L"SCRIPT") == 0 ) $binScript = true; // starting a script, which we need to ignore
				else if( $tagName.CompareNoCase(L"/SCRIPT") == 0 ) $binScript = false; // ending a script
				else if( $tagName.CompareNoCase(L"STYLE") == 0 ) $binStyle = true; // starting a style, which we need to ignore
				else if( $tagName.CompareNoCase(L"/STYLE") == 0 ) $binStyle = false; // ending a style
				else if( $tagName.CompareNoCase(L"HEAD") == 0 ) $binHeader = true; // starting a style, which we need to ignore
				else if( $tagName.CompareNoCase(L"/HEAD") == 0 ) $binHeader = false; // ending a style
				else if( $tagName.CompareNoCase(L"META") == 0 )
				{
					// we have a meta tag, which might change our character set
					$tagBody.MakeLower(); // make the tag body all lower case
					if($tagBody.Find(L"charset") > 0) // look for the $word charset
					{
						if($tagBody.Find(L"utf-8") > 0) m_UTF8 = true; // look for utf-8
						else m_UTF8 = false; // otherwise we're in another charset and can't use the utf-8 decoding
					}
				}
				continue; // otherwise keep reading
			}
			if($binScript || $binStyle || $binHeader) continue; // skip if we're in the middle of script or style or header
			else if(iswspace($m_char)) // check for white space
			{
				$delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
				$m_gotDelimiter=true;
			}
			else if(iswcntrl($m_char)) continue; // skip other control characters
			else if($m_gotDelimiter) // have we just reached the end of one or more delimiters?
			{
				if($m_gotword) // make sure that we have a $word
				{
					$word[$wordLength]=0; // finish the $word off
					$m_gotChar=true;
					return -1;
				}
				else // these were preliminary delimiters and we will ignore them
				{
					$delimiterType=DEL_TYPE_NONE;
					$m_gotDelimiter=false;
					$m_gotChar=true;
				}
			}
			else if($m_char == '&') // skip and ignore &xxx; codes.
			{
				$ampBuffer[256];
				$ampBufferCount = 0;
				while(true)
				{
					$m_char=GetCharHtml(); // read the next character
					if( (!iswalnum($m_char)) && ($m_char != '#')) break; // keep reading until we hit a non-alphanumeric, which should be a ';')
					if( $m_char < 0) break; // we hit an EOF before the end of the & code
					if($ampBufferCount < 255)
					{
						$ampBuffer[$ampBufferCount] = $m_char;
						$ampBufferCount++;
					}
				}
				$ampBuffer[$ampBufferCount] = 0; // finish off the string
				if($ampBuffer[0] == '#')
				{
					if($ampBuffer[1] == 'x') swscanf_s($ampBuffer+2,L"%x",&$m_char); // read in the hexidecial number directly
					else swscanf_s($ampBuffer+1,L"%d",&$m_char); // read in the character number directly
				}

				else if(wcscmp($ampBuffer,L"trade") == 0) $m_char = 153;
				else if(wcscmp($ampBuffer,L"nbsp") == 0) $m_char = 160;
				else if(wcscmp($ampBuffer,L"iexcl") == 0) $m_char = 161;
				else if(wcscmp($ampBuffer,L"cent") == 0) $m_char = 162;
				else if(wcscmp($ampBuffer,L"pound") == 0) $m_char = 163;
				else if(wcscmp($ampBuffer,L"yen") == 0) $m_char = 165;
				else if(wcscmp($ampBuffer,L"sect") == 0) $m_char = 167;
				else if(wcscmp($ampBuffer,L"copy") == 0) $m_char = 169;
				else if(wcscmp($ampBuffer,L"laquo ") == 0) $m_char = 171;
				else if(wcscmp($ampBuffer,L"reg") == 0) $m_char = 174;
				else if(wcscmp($ampBuffer,L"plusmn") == 0) $m_char = 177;
				else if(wcscmp($ampBuffer,L"micro") == 0) $m_char = 181;
				else if(wcscmp($ampBuffer,L"para") == 0) $m_char = 182;
				else if(wcscmp($ampBuffer,L"middot") == 0) $m_char = 183;
				else if(wcscmp($ampBuffer,L"raquo") == 0) $m_char = 187;
				else if(wcscmp($ampBuffer,L"iquest") == 0) $m_char = 191;
				else if(wcscmp($ampBuffer,L"Agrave") == 0) $m_char = 192;
				else if(wcscmp($ampBuffer,L"Aacute") == 0) $m_char = 193;
				else if(wcscmp($ampBuffer,L"Acirc") == 0) $m_char = 194;
				else if(wcscmp($ampBuffer,L"Atilde") == 0) $m_char = 195;
				else if(wcscmp($ampBuffer,L"Auml") == 0) $m_char = 196;
				else if(wcscmp($ampBuffer,L"Aring") == 0) $m_char = 197;
				else if(wcscmp($ampBuffer,L"AElig") == 0) $m_char = 198;
				else if(wcscmp($ampBuffer,L"Ccedil") == 0) $m_char = 199;
				else if(wcscmp($ampBuffer,L"Egrave") == 0) $m_char = 200;
				else if(wcscmp($ampBuffer,L"Eacute") == 0) $m_char = 201;
				else if(wcscmp($ampBuffer,L"Ecirc") == 0) $m_char = 202;
				else if(wcscmp($ampBuffer,L"Euml") == 0) $m_char = 203;
				else if(wcscmp($ampBuffer,L"Igrave") == 0) $m_char = 204;
				else if(wcscmp($ampBuffer,L"Iacute") == 0) $m_char = 205;
				else if(wcscmp($ampBuffer,L"Icirc") == 0) $m_char = 206;
				else if(wcscmp($ampBuffer,L"Iuml") == 0) $m_char = 207;
				else if(wcscmp($ampBuffer,L"Ntilde") == 0) $m_char = 209;
				else if(wcscmp($ampBuffer,L"Ograve") == 0) $m_char = 210;
				else if(wcscmp($ampBuffer,L"Oacute") == 0) $m_char = 211;
				else if(wcscmp($ampBuffer,L"Ocirc") == 0) $m_char = 212;
				else if(wcscmp($ampBuffer,L"Otilde") == 0) $m_char = 213;
				else if(wcscmp($ampBuffer,L"Ouml") == 0) $m_char = 214;
				else if(wcscmp($ampBuffer,L"Oslash") == 0) $m_char = 216;
				else if(wcscmp($ampBuffer,L"Ugrave") == 0) $m_char = 217;
				else if(wcscmp($ampBuffer,L"Uacute") == 0) $m_char = 218;
				else if(wcscmp($ampBuffer,L"Ucirc") == 0) $m_char = 219;
				else if(wcscmp($ampBuffer,L"Uuml") == 0) $m_char = 220;
				else if(wcscmp($ampBuffer,L"szlig") == 0) $m_char = 223;
				else if(wcscmp($ampBuffer,L"agrave ") == 0) $m_char = 224;
				else if(wcscmp($ampBuffer,L"aacute ") == 0) $m_char = 225;
				else if(wcscmp($ampBuffer,L"acirc ") == 0) $m_char = 226;
				else if(wcscmp($ampBuffer,L"atilde ") == 0) $m_char = 227;
				else if(wcscmp($ampBuffer,L"auml ") == 0) $m_char = 228;
				else if(wcscmp($ampBuffer,L"aring ") == 0) $m_char = 229;
				else if(wcscmp($ampBuffer,L"aelig ") == 0) $m_char = 230;
				else if(wcscmp($ampBuffer,L"ccedil ") == 0) $m_char = 231;
				else if(wcscmp($ampBuffer,L"egrave ") == 0) $m_char = 232;
				else if(wcscmp($ampBuffer,L"eacute ") == 0) $m_char = 233;
				else if(wcscmp($ampBuffer,L"ecirc ") == 0) $m_char = 234;
				else if(wcscmp($ampBuffer,L"euml ") == 0) $m_char = 235;
				else if(wcscmp($ampBuffer,L"igrave ") == 0) $m_char = 236;
				else if(wcscmp($ampBuffer,L"iacute ") == 0) $m_char = 237;
				else if(wcscmp($ampBuffer,L"icirc ") == 0) $m_char = 238;
				else if(wcscmp($ampBuffer,L"iuml ") == 0) $m_char = 239;
				else if(wcscmp($ampBuffer,L"ntilde ") == 0) $m_char = 241;
				else if(wcscmp($ampBuffer,L"ograve ") == 0) $m_char = 242;
				else if(wcscmp($ampBuffer,L"oacute ") == 0) $m_char = 243;
				else if(wcscmp($ampBuffer,L"ocirc ") == 0) $m_char = 244;
				else if(wcscmp($ampBuffer,L"otilde ") == 0) $m_char = 245;
				else if(wcscmp($ampBuffer,L"ouml ") == 0) $m_char = 246;
				else if(wcscmp($ampBuffer,L"divide") == 0) $m_char = 247;
				else if(wcscmp($ampBuffer,L"oslash ") == 0) $m_char = 248;
				else if(wcscmp($ampBuffer,L"ugrave ") == 0) $m_char = 249;
				else if(wcscmp($ampBuffer,L"uacute ") == 0) $m_char = 250;
				else if(wcscmp($ampBuffer,L"ucirc ") == 0) $m_char = 251;
				else if(wcscmp($ampBuffer,L"uuml ") == 0) $m_char = 252;
				else if(wcscmp($ampBuffer,L"yuml") == 0) $m_char = 255;
				else if(wcscmp($ampBuffer,L"quot") == 0) $m_char = 34;
				else if(wcscmp($ampBuffer,L"amp") == 0) $m_char = 38;
				else if(wcscmp($ampBuffer,L"lt") == 0) $m_char = 60;
				else if(wcscmp($ampBuffer,L"gt") == 0) $m_char = 62;
				else if(wcscmp($ampBuffer,L"ndash") == 0) $m_char = 8211;
				else if(wcscmp($ampBuffer,L"mdash") == 0) $m_char = 8212;
				else if(wcscmp($ampBuffer,L"lsquo") == 0) $m_char = 8216;
				else if(wcscmp($ampBuffer,L"rsquo") == 0) $m_char = 8217;
				else if(wcscmp($ampBuffer,L"ldquo") == 0) $m_char = 8220;
				else if(wcscmp($ampBuffer,L"rdquo") == 0) $m_char = 8221;
				else if(wcscmp($ampBuffer,L"euro") == 0) $m_char = 8364;
				else continue; // if we couldn't figure out what this character is, skip it

				if($wordLength < $wordMAXIMUMLENGTH)
				{
					$word[$wordLength]=$m_char; // add this character to the $word
					$wordLength++;
					$m_gotword=true;
				}
			}
			else if($wordLength < $wordMAXIMUMLENGTH)
			{
				$word[$wordLength]=$m_char; // add this character to the $word
				$wordLength++;
				$m_gotword=true;
			}
		}
	}
	else if( $this->m_contentType == CONTENT_TYPE_TXT )
	{
		while(true)
		{
			if($m_gotChar) $m_gotChar = false; // check to see if we already have the next character
			else $m_char=GetCharTxt(); // otherwise, get the next character (normal or UTF-8)
			
			if($m_char < 0) // check for EOF encountered
			{
				$word[$wordLength]=0; // finish the $word off
				$delimiterType = DEL_TYPE_EOF;
				return -1;
			}
			else if( ($m_char == '\n') || ($m_char == '\r') ) // check for newline characters
			{
				$delimiterType=DEL_TYPE_NEWLINE;
				$m_gotDelimiter=true;
			}
			else if(iswspace($m_char)) // check for white space
			{
				$delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
				$m_gotDelimiter=true;
			}
			else if( (iswcntrl($m_char) && $m_char < 0x80) || ($m_char == 0xff) ) continue; // skip any other control characters
			else if($m_gotDelimiter) // have we just reached the end of one or more delimiters?
			{
				if($m_gotword) // make sure that we have a $word
				{
					$word[$wordLength]=0; // finish the $word off
					$m_gotChar=true;
					return -1;
				}
				else // these were preliminary delimiters and we will ignore them
				{
					$delimiterType=DEL_TYPE_NONE;
					$m_gotDelimiter=false;
					$m_gotChar=true;
				}
			}
			else if($wordLength < $wordMAXIMUMLENGTH)
			{
				$word[$wordLength]=$m_char; // add this character to the $word
				$wordLength++;
				$m_gotword=true;
			}
		}
	}
	else if($this->m_contentType == CONTENT_TYPE_DOC)
	{
		while(true)
		{
			if($m_gotChar) $m_gotChar = false; // check to see if we already have the next character
			else $m_char=fgetc( $this->m_filep);
			
			if($m_char == 0) $m_char=fgetc( $this->m_filep); // skip a single null character (but not multiple nulls)
			
			if($m_char >= 0x80)	// convert extended ISO8559-1 characters into appropriate unicode characters
			{
				switch( $m_char )
				{
				case 128: $m_char = 8364; break;
				case 129: $m_char = 32; break;
				case 130: $m_char = 8218; break;
				case 131: $m_char = 402; break;
				case 132: $m_char = 8222; break;
				case 133: $m_char = 8230; break;
				case 134: $m_char = 8224; break;
				case 135: $m_char = 8225; break;
				case 136: $m_char = 170; break;
				case 137: $m_char = 8240; break;
				case 138: $m_char = 353; break;
				case 139: $m_char = 8249; break;
				case 140: $m_char = 339; break;
				case 141: $m_char = 32; break;
				case 142: $m_char = 381; break;
				case 143: $m_char = 32; break;
				case 144: $m_char = 32; break;
				case 145: $m_char = 8216; break;
				case 146: $m_char = 8217; break;
				case 147: $m_char = 8220; break;
				case 148: $m_char = 8221; break;
				case 149: $m_char = 8226; break;
				case 150: $m_char = 8211; break;
				case 151: $m_char = 8212; break;
				case 152: $m_char = 732; break;
				case 153: $m_char = 8482; break;
				case 154: $m_char = 353; break;
				case 155: $m_char = 8250; break;
				case 156: $m_char = 339; break;
				case 157: $m_char = 32; break;
				case 158: $m_char = 382; break;
				case 159: $m_char = 376; break;
				}
			}
			else if($m_char < 0) // check for EOF encountered
			{
				$word[$wordLength]=0; // finish the $word off
				$delimiterType = DEL_TYPE_EOF;
				return -1;
			}
			else if( ($m_char == '\n') || ($m_char == '\r')) // check for newline characters
			{
				$delimiterType=DEL_TYPE_NEWLINE;
				$m_gotDelimiter=true;
			}
			else if( ($m_char == '\t') || ($m_char == ' ')) // check for tab or space characters
			{
				$delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
				$m_gotDelimiter=true;
			}
			else if ( iswcntrl($m_char) && ($m_char < 0x80) ) // if we encounter a control character, restart the $word search
			{
				$word[0]=0; // start with empty $word, in case we encounter EOF before we encounter a $word
				$wordLength = 0;
				$m_gotword = false;
				$m_gotDelimiter = false;
				$delimiterType = DEL_TYPE_NONE;
			}
			else if($this->m_bBasic_Characters && ($m_char >= 0x80 )) // if we're using basic characters only and this is a non-basic character, restart the $word search
			{
				$word[0]=0; // start with empty $word, in case we encounter EOF before we encounter a $word
				$wordLength = 0;
				$m_gotword = false;
				$m_gotDelimiter = false;
				$delimiterType = DEL_TYPE_NONE;
			}
			else if($m_gotDelimiter) // have we just reached the end of one or more delimiters?
			{
				if($m_gotword) // make sure that we have a $word
				{
					$word[$wordLength]=0; // finish the $word off
					$m_gotChar=true;
					return -1;
				}
				else // these were preliminary delimiters and we will ignore them
				{
					$delimiterType=DEL_TYPE_NONE;
					$m_gotDelimiter=false;
					$m_gotChar=true;
				}
			}
			else if( $wordLength < $wordMAXIMUMLENGTH )
			{
				$word[$wordLength]=$m_char; // add this character to the $word
				$wordLength++;
				$m_gotword=true;
			}
		}
	}
	else if( $this->m_contentType == CONTENT_TYPE_PDF )
	{
		while(true)
		{
			if($m_gotChar) $m_gotChar = false; // check to see if we already have the next character
			else
			{
				$m_char=GetCharPdf();
			}
			
			if($m_char < 0) // check for EOF encountered
			{
				$word[$wordLength]=0; // finish the $word off
				$delimiterType = DEL_TYPE_EOF;
				return -1;
			}
			else if( ($m_char == '\n') || ($m_char == '\r') ) // check for newline characters
			{
				$delimiterType=DEL_TYPE_NEWLINE;
				$m_gotDelimiter=true;
			}
			else if(iswspace($m_char)) // check for white space
			{
				$delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
				$m_gotDelimiter=true;
			}
			else if( (iswcntrl($m_char) && $m_char < 0x80) || ($m_char == 0xff) ) continue; // skip any other control characters
			else if($m_gotDelimiter) // have we just reached the end of one or more delimiters?
			{
				if($m_gotword) // make sure that we have a $word
				{
					$word[$wordLength]=0; // finish the $word off
					$m_gotChar=true;
					return -1;
				}
				else // these were preliminary delimiters and we will ignore them
				{
					$delimiterType=DEL_TYPE_NONE;
					$m_gotDelimiter=false;
					$m_gotChar=true;
				}
			}
			else if( ($m_char >= 0xfb00) && ($m_char <= 0xfb06) )	// check for ligatured charactures
			{
				if($wordLength < $wordMAXIMUMLENGTH-3)
				{
					switch( $m_char )
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
					$m_gotword=true;
				}
			}
			else if($wordLength < $wordMAXIMUMLENGTH)
			{
				$word[$wordLength]=$m_char; // add this character to the $word
				$wordLength++;
				$m_gotword=true;
			}
		}
	}
	else // unknown type, so read bytes
	{
		while(true)
		{
			if($m_gotChar) $m_gotChar = false; // check to see if we already have the next character
			else $m_char=fgetc($this->m_filep);
			
			if($m_char == 0) $m_char=fgetc( $this->m_filep); // skip a single null character (but not multiple nulls)
			if($m_char < 0) // check for EOF encountered
			{
				$word[$wordLength]=0; // finish the $word off
				$delimiterType = DEL_TYPE_EOF;
				return -1;
			}
			else if( ($m_char == '\n') || ($m_char == '\r')) // check for newline characters
			{
				$delimiterType=DEL_TYPE_NEWLINE;
				$m_gotDelimiter=true;
			}
			else if( ($m_char == '\t') || ($m_char == ' ')) // check for tab or space characters
			{
				$delimiterType=max($delimiterType,DEL_TYPE_WHITE); // if delimiter isn't already at NEWLINE, set it to WHITE
				$m_gotDelimiter=true;
			}
			else if ( (iswcntrl($m_char) && $m_char < 0x80) || ($m_char == 0xff) ) // if we encounter a control character, restart the $word search
			{
				$word[0]=0; // start with empty $word, in case we encounter EOF before we encounter a $word
				$wordLength = 0;
				$m_gotword = false;
				$m_gotDelimiter = false;
				$delimiterType = DEL_TYPE_NONE;
			}
			else if($m_gotDelimiter) // have we just reached the end of one or more delimiters?
			{
				if($m_gotword) // make sure that we have a $word
				{
					$word[$wordLength]=0; // finish the $word off
					$m_gotChar=true;
					return -1;
				}
				else // these were preliminary delimiters and we will ignore them
				{
					$delimiterType=DEL_TYPE_NONE;
					$m_gotDelimiter=false;
					$m_gotChar=true;
				}
			}
			else if( $wordLength < $wordMAXIMUMLENGTH )
			{
				$word[$wordLength]=$m_char; // add this character to the $word
				$wordLength++;
				$m_gotword=true;
			}
		}
	}
	return -1;
}

    function documentToHtml($indoc,  $MatchMark, $MatchAnchor,  $words, $href)
    {
        $this->m_fHtml= fopen($indoc->file.".html", "w");				// create and open main comparison report text file
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
                if($LastMatch==WORD_PERFECT) fprintf($this->m_fHtml,"</font>");	// close out red markups if they were active
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
                        if(settings::$m_bBriefReport && ($wordcount>0) ) fprintf($this->m_fHtml,"</P>\n<P>");	// pr$a paragraph mark for a new line
                        fprintf($this->m_fHtml,"<a name='%i' $href='%s#%i'>",$MatchAnchor[$wordcount],$href,$MatchAnchor[$wordcount]);	// start new anchor
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
                //$iReturn = $indoc.GetWord($word,$DelimiterType); if($iReturn > -1) return $iReturn;	// get next word
                $iReturn = fgetcsv($indoc, 0, ' '); //if($iReturn > -1) return $iReturn;	// get next word

                // wcscpy_s($tword,$word);								// copy word to a temporary
                // Todo get correct settings somehow
                // if(defsettings::$m_bIgnorePunctuation) WordRemovePunctuation($tword);	// if ignore punctuation is active, remove punctuation
                // if(defsettings::$m_bIgnoreOuterPunctuation) wordxouterpunct($tword);	// if ignore outer punctuation is active, remove outer punctuation
                // if(defsettings::$m_bIgnoreNumbers) WordRemoveNumbers($tword);			// if ignore numbers is active, remove numbers
                // if(defsettings::$m_bIgnoreCase) WordToLowerCase($tword);			// if ignore case is active, remove case
                // if(defsettings::$m_bSkipLongWords $ (wcslen($tword) > settings::$m_SkipLength) ) continue;	// if skip too-long $words is active, skip them
                // if(defsettings::$m_bSkipNonwords $ (!WordCheck($tword)) ) continue;	// if skip non$words is active, skip them

                break;
            }
        
            if( (!defsettings::$m_bBriefReport) || ($xMatch == WORD_PERFECT) || ($xMatch == WORD_FLAW) )
            {
                // $wordLength=wcslen($word);						// find length of word
                // for($i=0;$i<$wordLength;$i++) PrintWCharAsHtmlUTF8($this->m_fHtml,$word[i]);			// pr$the character, using UTF8 translation
                if($DelimiterType == DEL_TYPE_WHITE) fprintf($this->m_fHtml," ");					// pr$a blank for white space
                else if($DelimiterType == DEL_TYPE_NEWLINE) fprintf($this->m_fHtml,"<br>");			// pr$a break for a new line
            }
        }
        if($LastMatch==WORD_PERFECT) fprintf($this->m_fHtml,"</font>");	// close out red markups if they were active
        else if($LastMatch==WORD_FLAW) fprintf($this->m_fHtml,"</font></i>");	// close out green italics if they were active
        else if($LastMatch==WORD_FILTERED)  fprintf($this->m_fHtml,"</font>");	// close out blue markups if they were active
        if($LastAnchor>0) fprintf($this->m_fHtml,"</a>");	// close out any active anchor
        return ;
    }
}
