<?php
    namespace Fiets\Util;

	class Names {

        /**
         * Expected types of names
         * 1. Joris (only first name)
         * 2. Joris Leker (simplest case)
         * 3. Bibi van Hoeflaken (single tussenvoegsel)
         * 4. Jan Hendrik Leker (first name consits of multiple words, no tussenvoesel)
         * 5. Jan Hendrik van Hoeflaken (first name consists of multiple words, tussenvoegsel)
         * 6. Bjorn Post - van der Heijden (tussenvoegsel within surname)
         * 7. Helene van den Dries (single tussenvoegsel, multiple words)
         * 8. Helene van den Dries - Post (achternaam consists of mutiple words)
         * 9. Helene van den Dries - van der Heijden (achternaam consists of multiple words and contains own tussenvoegsel of multiple words
         * 10. Dhr. van den Broek (No firstname, but gender prefix)

         *
         * There is no way to distinguish case 5 and 6 automatically, but voor/achternaam and tussenvoegsel contain different parts.
         * these case need manual inspection
         */


        public static function extractParts($name) {

            $nameCopy = $name;

            $processManually = false;
            $geslacht = null;
            $voornaam = null;
            $tussenvoegsel = null;
            $achternaam = null;

            // 1. Replace spaces in common tussenvoegsels, to make them one word
            $tussenvoegsels = ['van','de','ter','den','van \'t','de la', 'da','van der','van de','van den','vd','v.d.','v.d','du','von','le','op den','v\/d',];
            $transform = [];
            foreach ($tussenvoegsels as $t) {
                $transform[$t] = str_replace(' ', '**', $t);
                $nameCopy = preg_replace("/$t/i", $transform[$t], $nameCopy);
            }

            // 2. Make sure we properly treat '-' (used when name of partner is added to achternaam)
            $nameCopy = str_replace('-',' ##-## ', $nameCopy);

            $parts = preg_split('/[\s]+/', $nameCopy);

            // If we have a '##-##' part, then everything from it's index-1 till the end should be considered as a single part
            if (false !== $index = array_search('##-##', $parts)) {
                $parts[$index - 1] = implode(' ',array_slice($parts, $index-1));
                $parts = array_slice($parts, 0, $index);
            }


            // 2. Simple cases: one or two parts
            if (count($parts) === 1) {
                $voornaam = $parts[0];
            } else if (count($parts) === 2) {
                $voornaam = $parts[0];
                $achternaam = $parts[1];

            // 3. relatively simple case: part 2 is a tussenvoegsel
            } else if (in_array($parts[1], $transform)) {
                $voornaam = $parts[0];
                $tussenvoegsel = $parts[1];
                $achternaam = implode(' ', array_slice($parts, 2));

            // 4. We can't know for sure.
            } else {
                $voornaam = $name;
                $processManually = true;
            }

            // 5. Do we know anything about gender?
            if (in_array(strtolower($voornaam), ['meneer','dhr',])) {
                $geslacht = 'M';
                $voornaam = null;
            } else if (in_array(strtolower($voornaam), ['mevrouw','mevr',])) {
                $geslacht = 'F';
                $voornaam = null;
            }

            if ($tussenvoegsel !== null) {
                $tussenvoegsel = str_replace('**', ' ', $tussenvoegsel);
            }
            if ($achternaam !== null) {
                $achternaam = str_replace('**', ' ', $achternaam);
                $achternaam = str_replace(' ##-## ', '-', $achternaam);
            }

            return [
                'voornaam' => $voornaam,
                'tussenvoegsel' => $tussenvoegsel,
                'achternaam' => $achternaam,
                'geslacht' => $geslacht,
                '_processManually' => $processManually,
            ];

        }

    }