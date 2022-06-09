<?php


namespace plagiarism_mcopyfind\classes;

require('./Document.php');

//$doc1 = new document();

//open document
//check if its valid
class CompareDocuments
{
    //Settings
    static $phraseLength = 3;

    //set wordNumber to 0
    function main()
    {
        //loop until EOF
        //count words  - counting settings
        // echo getcwd(); //working directory -> with namespace workdir changes
        $documents = [];
        // $file = fopen("t01e.txt", "r");
        // $file2 = fopen("t01.txt", "r");
        // echo $file;
        // echo $file2;
        //$documents.put();
        array_push($documents, new Document("t01.txt"));
        array_push($documents, new Document("t01e.txt"));

        foreach ($documents as $doc) {
            $this->loadDocument($doc);
        }
    }

    static function loadDocument($document)
    {
        $file = fopen($document->path, "r");
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
                        $hashes[$document->wordNumber] = CompareDocuments::wordHash($element);

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
                        $pQWordHash[$document->wordNumber] = CompareDocuments::wordHash($element);
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

        //$heap= new HeapSort();
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

        if (CompareDocuments::$phraseLength == 1) $document->firstHash = 0;        // if phraselength is 1 word, compare even the shortest words
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


    //Guess how this works Probably

    //what it does is it takes 10000 Words of one document - words or characters
    //generates hashes for each word, then it sorts the hashes in a heap and then does it for the second document and then compares the heaps?

    //then generate html by exporting the document and marking the saved findings

    //Excluding vocab functionality for now, generating html as well!
    //specific word hash function

    


    /** Description of Hashing process
     * inhash    zzzzzzzxxxxxxxxxxxxxxxxxxxxxxxxxxxx
     * ----------------------------------------------
     * 
     * inhash <7 xxxxxxxxxxxxxxxxxxxxxxxxxxx-0000000
     * Logical or
     * inhash >25                           -zzzzzzz
     * =         xxxxxxxxxxxxxxxxxxxxxxxxxxxxzzzzzzz
     * Logical xor
     * char word                            -yyyyyyy
     * =         xxxxxxxxxxxxxxxxxxxxxxxxxxxxaaaaaaa
     */
    static function WordHash($word)
    {
        //unsigned long inhash;
        //$inhash;
        $inhash = 0;
        //$word .= " "; 
        $charcount = 0;
        $length = strlen($word);
        if ($length == 0) return 1;    // if word is null, return 1 as hash value
        else while ($charcount != $length) {
            $inhash = (($inhash << 7) | ($inhash >> 25)) ^ mb_ord($word[$charcount]);    // xor into the rotateleft(7) of inhash
            $charcount++;                            // and increment the count of characters in the word
        }
        return abs($inhash);
    }
}

$test = new CompareDocuments();
$test->main();
