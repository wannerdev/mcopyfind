<?php
namespace plagiarism_mcopyfind\compare;

include('./document.php');
include('./settings.php');
include('./words.php');
include('./generate_report.php');
include('./compare_functions.php');



//open document
//check if its valid
class load_documents
{

    //set wordNumber to 0
    public $documents = [];
    public $settings;
        // private function __construct(){
    //     $this->settings=new settings();
    // }

    function __construct(){
        $this->settings=new settings();
        
    }

    function testMain()
    {
        //loop until EOF
        //count words  - counting settings
        // echo getcwd(); //working directory -> with namespace workdir changes
        // $file = fopen("t01e.txt", "r");
        // $file2 = fopen("t01.txt", "r");
        // echo $file;
        // echo $file2;
        //$documents.put();
        $doc1=new Document();
        $doc1->definePath("t01.txt");
        $doc2=new Document();
        $doc2->definePath("t01e.txt");
        array_push($this->documents, $doc2);
        array_push($this->documents, $doc1);

        foreach ($this->documents as $doc) {
            $this->loadDocument($doc);
        }

        $reportGen= new generate_report($this->settings);
        
        $cmp = new compare_functions($this->settings);
        $cmp->ComparePair($this->documents[0],$this->documents[1]);

        // $reportGen->DocumentToHtml($this->documents[0], $cmp->m_MatchMarkL, $cmp->m_MatchAnchorL, $cmp->words, $cmp->href);
        // $reportGen->DocumentToHtml($this->documents[1], $cmp->m_MatchMarkR, $cmp->m_MatchAnchorR, $cmp->words, $cmp->href);
        $reportGen->FinishReports();
        //function generateReport(Document $inputDoc, $MatchAnchor, $words, $href)
        $matchanch =400;
        $words= ["test","test2"];
        $href= "<a href='test'>test</a>";
        // $reportGen->generateReport($this->documents[0], $matchanch,   $words, $href);
    }

    /**
     * Maybe make static to improve performance later
     */
    function loadDocument($document)
    {
        $document->openDocument();
        $file = fopen($document->path, "r");
        if($file)return "ERROR: File not found";
        $document->firstHash = null;

        $wordNumber = 0;
        $words = 0;
        $hashes = [];
        $realwords = 0;
        $wordsAllocated = 0;
        $wordInc = 10000; //Default increment in characters to compare ?

        // $words;
        // $wordNumber;
        $pXWordHash = [];
        $pQWordHash = [];
        //echo $document->file."WHaaat";
        // var_dump($document->file);
        if ($file) {
            while (!feof($file)) {
                $wordsPars = fgetcsv($file, 0, ' ');
                if ($wordsPars) {
                    foreach ($wordsPars as $element) {
                        //  print_r($element. "<br><br>");
                        $hashes[$document->wordNumber] = words::WordHash($element);

                        if ($hashes[$document->wordNumber] != 1) {
                            // print_r($hashes[$document->wordNumber] . "<br><br>");
                            $realwords++;
                        }
                        if ($document->wordNumber == $wordsAllocated)                            // if hash-coded word entries are full (or don't exist)
                        {
                            $wordsAllocated += $wordInc;                            // increase maximum number of words
                            if ($pXWordHash != NULL) $pXWordHash = null;        // if allocated, delete temporary hash-coded word list
                            $pXWordHash = [$wordsAllocated];        // allocate new, larger array of entries
                            if ($pXWordHash == NULL) print_error("ERR_CANNOT_ALLOCATE_WORKING_HASH_ARRAY");

                            if ($pQWordHash != NULL) {
                                for ($i = 0; $i < $wordNumber; $i++) $pXWordHash[$i] = $pQWordHash[$i];    // copy hash-coded word entries to new array
                                $pQWordHash = null;    // if allocated, delete temporary hash-coded word list
                            }

                            $pQWordHash = $pXWordHash;                            // set normal pointer to new, larger array
                            $pXWordHash = NULL;
                        }
                        $document->wordNumber++;
                        $pQWordHash[$document->wordNumber] = words::wordHash($element);
                    }
                }
            }
            fclose($file);
        } else {
            return;
        }
        $document->words = $document->wordNumber;

        $document->pWordHash = [$words];        // allocate array for hash-coded words in doc entry
        $document->pSortedWordHash = [$words];    // allocate array for sorted hash-coded words
        $document->pSortedWordNumber = [$words];            // allocate array for sorted word numbers


        for ($i = 0; $i < $words; $i++)            // loop for all the words in the document
        {
            $document->pQWordHash[$i] = $pQWordHash[$i];                // copy over hash-coded words
            $document->pSortedWordNumber[$i] = $i;                    // copy over word numbers
            $document->pSortedWordHash[$i] = $pQWordHash[$i];        // copy over hash-coded words
        }

        //$heap= new heapsort();
        //$result= HeapSort::HeapSorting($pSortedWordHash,				// sort hash-coded words (and word numbers)
        //$pSortedWordNumber, $words);
        //var_dump($result);
        //var_dump($pSortedWordNumber);
        // foreach ($document->pSortedWordNumber as $element) {
        //     echo ($element . "<br>");
        // }
        // echo ("<br><br>");
        // foreach ($document->pSortedWordHash as $element) {
        //     echo ($element . "<br>");
        // }

        if ($this->settings->phraseLength == 1) $document->firstHash = 0;        // if phraselength is 1 word, compare even the shortest words
        else                                                        // if phrase length is > 1 word, start at first word with more than 3 chars
        {
            $firstLong = 0;
            for ($i = 0; $i < $words; $i++)                                    // loop for all the words in the document
            {
                if (($document->pSortedWordHash[$i] & 0xFFC00000) != 0)    // if the word is longer than 3 letters, break
                {
                    $firstLong = $i;
                    break;
                }
            }
            $document->firstHash = $firstLong;                    // save the number of the first >3 letter word, or the first word
        }

        //Test output
        // print_r("<h1>" . $document->wordNumber . "  Real " . $document->realwords . "</h1>");
        // fclose($file);
        // print_r("<h1> ERROR</h1>");
        // rint_error("stop");
    }

    function CloseDocument($file){
        fclose($file);
    }
    //Guess how this works Probably
    //what it does is it takes 10000 Words of one document - words or characters?
    //generates hashes for each word, then it sorts the hashes in a heap and then does it for the second document and then compares the heaps?
    //then generate html by exporting the document and marking the saved findings
    //Excluding vocab functionality for now,
    //specific word hash function
}


//Testcase 1
 $test = new load_documents();
 $test->testMain();

 echo file_get_contents("C:\\reports\\matches.html");