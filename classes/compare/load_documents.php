<?php
namespace plagiarism_mcopyfind\compare;

// echo getcwd() . __DIR__; //working directory -> with namespace workdir changes
require(__DIR__.'\document.php');
require(__DIR__.'\settings.php');
require(__DIR__.'\heapsort.php');
require(__DIR__.'\words.php');
require(__DIR__.'\generate_report.php');
require(__DIR__.'\compare_functions.php');

const  ERR_ABORT =0;
const  ERR_CANNOT_OPEN_FILE=1;
const  ERR_CANNOT_ALLOCATE_WORKING_HASH_ARRAY=2;
const  ERR_CANNOT_ALLOCATE_HASH_ARRAY=3;
const  ERR_CANNOT_ALLOCATE_SORTED_HASH_ARRAY=4;
const  ERR_CANNOT_ALLOCATE_SORTED_NUMBER_ARRAY=5;
const  ERR_CANNOT_OPEN_LOG_FILE=6;
const  ERR_CANNOT_OPEN_COMPARISON_REPORT_TXT_FILE=7;
const  ERR_CANNOT_OPEN_COMPARISON_REPORT_HTML_FILE=8;
const  ERR_CANNOT_ALLOCATE_LEFT_MATCH_MARKERS=9;
const  ERR_CANNOT_ALLOCATE_RIGHT_MATCH_MARKERS=10;
const  ERR_CANNOT_ALLOCATE_LEFTA_MATCH_MARKERS=11;
const  ERR_CANNOT_ALLOCATE_RIGHTA_MATCH_MARKERS=12;
const  ERR_CANNOT_ALLOCATE_LEFTT_MATCH_MARKERS=13;
const  ERR_CANNOT_ALLOCATE_RIGHTT_MATCH_MARKERS=14;
const  ERR_CANNOT_OPEN_LEFT_HTML_FILE=15;
const  ERR_CANNOT_OPEN_LEFT_DOCUMENT_FILE=16;
const  ERR_CANNOT_OPEN_RIGHT_HTML_FILE=17;
const  ERR_CANNOT_OPEN_RIGHT_DOCUMENT_FILE=18;
const  ERR_CANNOT_OPEN_SIDE_BY_SIDE_HTML_FILE=19;

const  ERR_CANNOT_ACCESS_URL =101;
const  ERR_NO_FILE_OPEN =102;
const  ERR_CANNOT_FIND_FILE =103;
const  ERR_CANNOT_FIND_FILE_EXTENSION =104;
const  ERR_BAD_DOCX_FILE= 105;
const  ERR_BAD_PDF_FILE= 106;
const  ERR_CANNOT_FIND_URL_LINK =107;
const  ERR_CANNOT_OPEN_INPUT_FILE =108;

class load_documents
{

    //set wordNumber to 0
    public $documents = [];
    private static $settings;
    public $wordsize=10000;
    public $wordInc=1000;
    public $wordsFunc;

    function __construct(){
        self::$settings=new settings();        
        $this->wordsFunc = new words();
    }

    static function getSettings(){
        if(self::$settings == null){
            self::$settings = new settings();
            echo("Settings not loaded, using defaults");
        }
        return self::$settings; 
    }

    function testMain()
    {
        //count wordAmount  - counting settings
        // echo getcwd(); //working directory -> with namespace workdir changes
        
        //$doc1=new Document("t02.rtf");
         $doc1=new Document("t01.txt");
        //$doc1 = mew document("fund1.pdf");

        //$doc2=new Document("t02d.rtf");
         $doc2=new Document("t01e.txt");

        $doc1->m_DocumentType=DOC_TYPE_NEW;
        $doc2->m_DocumentType=DOC_TYPE_NEW;

        array_push($this->documents, $doc2);
        array_push($this->documents, $doc1);
        
        $cmp = new compare_functions( $this->documents,-1);
        $irvalue =$cmp->RunComparison($this);
        
        if($irvalue > -1)
        {
            echo ErrorcodeToString($irvalue);
        }
        
    }

    /**
     * Maybe make static to improve performance later
     */
    function loadDocument($document)
    {

        $wordNumber = 0;
        $wordAmount = 0;
        $hashes = [];
        $realwords = 0;
        $word = '';
        $DelimiterType = DEL_TYPE_NONE;
        // echo "\n################################\n";
        // echo "Document:".$document->path . "\n";
        // echo "\n################################\n";
        // var_dump($document->file);
        while ($DelimiterType != DEL_TYPE_EOF) {
            $word='';
            $document->Getword($word,$DelimiterType);
            if(load_documents::getSettings()->m_bIgnorePunctuation) $this->wordsFunc->WordRemovePunctuation($word);	// if ignore punctuation is active, remove punctuation
            if(load_documents::getSettings()->m_bIgnoreOuterPunctuation) $this->wordsFunc->wordxouterpunct($word);	// if ignore outer punctuation is active, remove outer punctuation
            if(load_documents::getSettings()->m_bIgnoreNumbers) $this->wordsFunc->WordRemoveNumbers($word);			// if ignore numbers is active, remove numbers
            if(load_documents::getSettings()->m_bIgnoreCase) $this->wordsFunc->WordToLowerCase($word);				// if ignore case is active, remove case
            // echo "WOrd: ".$word."\n";
            // echo "WOrdlength: ".strlen($word);
            if(load_documents::getSettings()->m_bSkipLongWords & (strlen($word) > load_documents::getSettings()->m_SkipLength) )continue;	// if skip too-long words is active, skip them
            if(load_documents::getSettings()->m_bSkipNonwords & (!$this->wordsFunc->WordCheck($word)) )continue;		// if skip nonwords is active, skip them
            
    
            // print_r("$word:".$word . "\n");
            // print_r("Hashes:".$hashes[$wordNumber] . "\n");
            // if ($hashes[$wordNumber] != 1) {
            //     $realwords++;
            // }
            
            if($wordNumber == $this->wordsize) {
                $this->wordsize += $this->wordInc;
            }

            $hashes[$wordNumber] = words::WordHash($word);
            
            // print_r("Word:".$word . "\n");
            // print_r($hashes[$wordNumber]." ");
            $wordNumber++;
            // echo "WOrd: ".$word."\n";
        }

        $wordAmount = $wordNumber;              // save number of wordAmount
        $document->m_WordsTotal=$wordAmount;	// save number of wordAmount in document entry

        for ($i = 0; $i < $wordAmount; $i++)            // loop for all the wordAmount in the document
        {
            $document->m_pWordHash[$i] = $hashes[$i];                // copy over hash-coded wordAmount
            $document->pSortedWordNumber[$i] = $i;                    // copy over word numbers
            $document->pSortedWordHash[$i] = $hashes[$i];        // copy over hash-coded wordAmount
        }
        // echo("START:");
        // var_dump($document->pSortedWordHash);
        $sorted = heapsort::HeapSorting($document->pSortedWordHash,				// sort hash-coded wordAmount (and word numbers)
                              $document->pSortedWordNumber, $wordAmount-1);
        
        $document->pSortedWordHash = $sorted[0];
        $document->pSortedWordNumber = $sorted[1];

        // Test output
        // print_r("<h1>" . $document->m_WordsTotal . "  Real " . $document->realwords . "</h1>");
        // var_dump($document->pSortedWordNumber);
        // echo("END: \n\n\n\n");
        // var_dump($document->pSortedWordHash);
        // foreach ($document->pSortedWordNumber as $element) {
        //   echo ($element . "<br>");
        // }

        // echo ("<br><br>");
        // print_r(" ------------------------------------------------- \n");
        // for($i=0; $i< count($document->pSortedWordHash); $i++) {
        //     echo ("".$document->pSortedWordHash[$i] . "\n");
        // }
        //echo ("".$document->pSortedWordHash->s . "\n");
        // sort($document->pSortedWordHash);
        //  var_dump($document->pSortedWordHash);
        //  foreach ($document->pSortedWordHash as $element) {
        //          echo ("".$element . " ");
        //  }

        if (load_documents::getSettings()->m_PhraseLength == 1) $document->firstHash = 0;        // if phraselength is 1 word, compare even the shortest words
        else                                                        // if phrase length is > 1 word, start at first word with more than 3 chars
        {
            $firstLong = 0;
            for ($i = 1; $i < $wordAmount; $i++)                                    // loop for all the words in the document
            {
                if ((ord($document->pSortedWordHash[$i]) & 0xFFC00000) != 0)    // if the word is longer than 3 letters, break
                {
                    $firstLong = $i;
                    break;
                }
            }
            $document->firstHash = $firstLong;                    // save the number of the first >3 letter word, or the first word            
            // echo ("setting firstHash: ".$document->firstHash . "\n");
        }
    }

    //Guess how this works Probably
    //what it does is it takes 10000 Words of one document - wordAmount or characters?
    // generates hashes for each word in every document (sorted by occurance basically)
    // then creates a sorted copy of the the hashes (sorted by hash value)
    // and then compares the heaps?


    //then generate html by exporting the document and marking the saved findings
    //Excluding vocab functionality for now,
    //specific word hash function

    //Improvment idea define header and footer to ignore
    //needs page number with range of words
    //List of pages with start number of a page,
    // Comparing starts always at page+ header and ends at page-footer //before comparing you ask if the word is inside the page range
}

 function ErrorcodeToString($irvalue){
    switch($irvalue) {
        case ERR_CANNOT_OPEN_FILE :
            $errorString=("Error: Could not open file during comparison process");
            break;
        case ERR_CANNOT_ALLOCATE_WORKING_HASH_ARRAY :
            $errorString=("Error: Could not allocate working space for hash array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_ALLOCATE_HASH_ARRAY :
            $errorString=("Error: Could not allocate hash array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_ALLOCATE_SORTED_HASH_ARRAY :
            $errorString=("Error: Could not allocate sorted hash array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_ALLOCATE_SORTED_NUMBER_ARRAY :
            $errorString=("Error: Could not allocate sorted number array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_OPEN_LOG_FILE :
            $errorString=("Error: Could not open log file during comparison process");
            break;
        case ERR_CANNOT_OPEN_COMPARISON_REPORT_TXT_FILE :
            $errorString=("Error: Could not open comparison report text file during comparison process");
            break;
        case ERR_CANNOT_OPEN_COMPARISON_REPORT_HTML_FILE :
            $errorString=("Error: Could not open comparioson report html file during comparison process");
            break;
        case ERR_CANNOT_ALLOCATE_LEFT_MATCH_MARKERS :
            $errorString=("Error: Could not allocate left match marker array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_ALLOCATE_RIGHT_MATCH_MARKERS :
            $errorString=("Error: Could not allocate right match marker array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_ALLOCATE_LEFTA_MATCH_MARKERS :
            $errorString=("Error: Could not allocate leftA match marker array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_ALLOCATE_RIGHTA_MATCH_MARKERS :
            $errorString=("Error: Could not allocate rightA match marker array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_ALLOCATE_LEFTT_MATCH_MARKERS :
            $errorString=("Error: Could not allocate leftT match marker array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_ALLOCATE_RIGHTT_MATCH_MARKERS :
            $errorString=("Error: Could not allocate rightT match marker array during comparison process. Possibly out of memory.");
            break;
        case ERR_CANNOT_OPEN_LEFT_HTML_FILE :
            $errorString=("Error: Could not open left html file during comparison process");
            break;
        case ERR_CANNOT_OPEN_LEFT_DOCUMENT_FILE :
            $errorString=("Error: Could not open left document file during comparison process");
            break;
        case ERR_CANNOT_OPEN_RIGHT_HTML_FILE :
            $errorString=("Error: Could not open right html file during comparison process");
            break;
        case ERR_CANNOT_OPEN_RIGHT_DOCUMENT_FILE :
            $errorString=("Error: Could not open right document file during comparison process");
            break;
        case ERR_CANNOT_OPEN_SIDE_BY_SIDE_HTML_FILE :
            $errorString=("Error: Could not open side-by-side html file during comparison process");
            break;
        case ERR_CANNOT_ACCESS_URL :
            $errorString=("Error: URL could not be accessed");
            break;
        case ERR_NO_FILE_OPEN :
            $errorString=("Software Bug: Trying to read from a file that is not open");
            break;
        case ERR_CANNOT_FIND_FILE :
            $errorString=("Error: File could not be found");
            break;
        case ERR_CANNOT_FIND_FILE_EXTENSION :
            $errorString=("Error: File is missing an extension and its type cannot be determined");
            break;
        case ERR_BAD_DOCX_FILE :
            $errorString=("Error: This docx file cannot be read properly");
            break;
        case ERR_BAD_PDF_FILE :
            $errorString=("Error: This pdf file cannot be read properly");
            break;
        case ERR_CANNOT_FIND_URL_LINK :
            $errorString=("Error: URL link cannot be found");
            break;
        case ERR_CANNOT_OPEN_INPUT_FILE :
            $errorString=("Error: File cannot be opened, perhaps because it is already opened by other software");
            break;
        default :
            $errorString=("Error Occurred During Comparison Process .$irvalue ");
        }
        return $errorString;
 }

//Testcase 1
// $test = new load_documents();
// $test->testMain();
// file_get_contents("C:\\moodle\\server\\moodle\\plagiarism\\mcopyfind\\reports\\-1matches.html");