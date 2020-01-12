<?php
require_once('wordList.php');

class walletGenerator{
    //class objects
    private $wordList = null;
    
    //external class variables
    private $wordListEN = null;
    
    //local variables
    private $randomNum = null;
    private $mnemonic = '';

        
    public function getMnemonic(){
        $this->wordList = new wordList();
        $this->wordListEN = $this->wordList->getWordListEN();
        
        
        
        for($i = 0;$i < 25;$i++){
            $this->randomNum = random_int(0,count($this->wordListEN)-1);
            $this->mnemonic = $this->mnemonic . ' ' . $this->wordListEN[$this->randomNum];
            
        }  
        
        $test = count($this->wordListEN) -1;
        $testing = $this->wordListEN[$test] . '<br>';
        return $testing;
    }
        
}
?>

<html>
    <body>

        <?php
        $walGEN = new walletGenerator();
        echo $walGEN->getMnemonic(); 
        
        
        
        ?>


    </body>
</html>