<?php
require_once('wordList.php');
require_once('lib/ed25519.php');
require_once('lib/base58.php');
require_once('lib/SHA3.php');

class walletGenerator {

    //class objects
    private $wordList = null;
    private $ed25519 = null;
    private $base58 = null;
    //external class variables
    private $wordListEN = null;
    //local variables
    private $wallet = array('mnemonic' => '', 'privateKey' => '', 'publicKey' => '');

    public function getMnemonic() {
        $this->wordList = new wordList();
        $this->wordListEN = $this->wordList->getWordListEN();

        $mnemonic = '';
        $mnemonic_array = [];
        $checksum = '';
        $crc32 = null;
        $randomNum = null;

        $randomNum = random_int(0, count($this->wordListEN) - 1);

        $mnemonic = $this->wordListEN[$randomNum];
        $checksum = substr($this->wordListEN[$randomNum], 0, 3);

        // loop to get 24 words for mnemonic seed
        for ($i = 0; $i < 23; $i++) {
            // get random number between 0 and max amount of words in wordlist
            $randomNum = random_int(0, count($this->wordListEN) - 1);
            // add word to mnemonic string
            $mnemonic = $mnemonic . ' ' . $this->wordListEN[$randomNum];

            $checksum = $checksum . substr($this->wordListEN[$randomNum], 0, 3);
        }

        $crc32 = crc32($checksum) % 24;
        $mnemonic_array = explode(' ', $mnemonic);

        $mnemonic = $mnemonic . ' ' . $mnemonic_array[$crc32];

        //return $this->mnemonic;
        return $mnemonic;
    }

    public function getPrivateKey($mnemonic) {
        $this->wordList = new wordList();
        $this->wordListEN = $this->wordList->getWordListEN();

        $privateKey = '';
        $w1 = '';
        $w2 = '';
        $w3 = '';
        $n = count($this->wordListEN);
        $x = null;

        //$mnemonic = 'height weekday ability mohawk gambit adopt inbound pavements criminal either left nouns malady aquarium null weavers mobile rumble session siren gypsy dotted fiat giving rumble';

        $words = explode(' ', $mnemonic);
        array_pop($words);

        if (count($words) == 24) {
            for ($i = 0; $i < count($words); $i += 3) {
                $w1 = array_search($words[$i], $this->wordListEN);
                $w2 = array_search($words[$i + 1], $this->wordListEN);
                $w3 = array_search($words[$i + 2], $this->wordListEN);

                if ($w1 === -1 || $w2 === -1 || $w3 === -1) {
                    //error, invalid word in mnemonic
                }

                $x = $w1 + $n * ((($n - $w1) + $w2) % $n) + $n * $n * ((($n - $w2) + $w3) % $n);

                if ($x % $n != $w1) {
                    //error, decoding failed
                }
                $privateKey = $privateKey . $this->mn_swap_endian_4byte(substr('0000000' . base_convert($x, 10, 16), -8));
            }


            return $privateKey;
        } else {
            return null;
        }
    }

    public function mn_swap_endian_4byte($str) {
        if (strlen($str) !== 8) {
            //error, invalid input length
        }
        $result = substr($str, 6, 2) . substr($str, 4, 2) . substr($str, 2, 2) . substr($str, 0, 2);
        return $result;
    }

    public function getPublicKey($privKey) {
        $this->base58 = new base58();

        //public spend key
        $psk = $this->pk_from_sk($privKey);
        //public view key
        $pvk = $this->pk_from_sk($this->derive_viewKey($privKey));

        // monero 18 / 12
        // catalyst 0x171f54 / d4be5c
        $data = "12" . $psk . $pvk;
        $publicKey = $this->base58->encode($data);
        return $publicKey;
    }

    public function pk_from_sk($privKey) {
        $this->ed25519 = new ed25519();
        $keyInt = $this->ed25519->decodeint(hex2bin($privKey));
        $aG = $this->ed25519->scalarmult_base($keyInt);
        return bin2hex($this->ed25519->encodepoint($aG));
    }

    public function derive_viewKey($spendKey) {
        return $this->hash_to_scalar($spendKey);
    }

    public function hash_to_scalar($data) {
        $hash = $this->keccak_256($data);
        $scalar = $this->sc_reduce($hash);
        return $scalar;
    }

    public function keccak_256($message) {
        $keccak256 = SHA3::init(SHA3::KECCAK_256);
        $keccak256->absorb(hex2bin($message));
        return bin2hex($keccak256->squeeze(32));
    }

    public function sc_reduce($input) {
        $this->ed25519 = new ed25519();
        $integer = $this->ed25519->decodeint(hex2bin($input));

        $modulo = bcmod($integer, $this->ed25519->l);

        $result = bin2hex($this->ed25519->encodeint($modulo));
        return $result;
    }

}
?>

<html>
    <body>

        <?php
        $walletGenerator = new walletGenerator();
        $getMnemonic = $walletGenerator->getMnemonic();
        //$tmp = 'pool cactus voted superior eccentric desk autumn oxidant ferry rest fever dinner nobody asked tusks abbey anchor pizza wizard pinched renting stellar artistic bacon oxidant';
        $tmp = 'plywood broken distance yahoo wipeout lively rage dwelt nobody tirade somewhere snout bite dating buzzer omission vexed linen jaunt certain loudly hive dinner dude buzzer';
        $privateKey = $walletGenerator->getPrivateKey($tmp);
        $publicKey = $walletGenerator->getPublicKey($privateKey);
        $count = count(explode(' ', $getMnemonic));

        echo $getMnemonic;
        echo '<br>';
        echo $count;
        echo '<br>';
        echo $privateKey;
        echo '<br>';
        echo $publicKey;
        echo '<br>';
        echo strlen($publicKey);
        ?>


    </body>
</html>