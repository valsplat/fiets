<?php

namespace Fiets\Util;

class Names
{
    /**
     * Expected types of names
     * 1. Henk (only first name)
     * 2. Henk Boer (simplest case)
     * 3. Henk de Boer (single tussenvoegsel)
     * 4. Henk Frederik Boer (first name consits of multiple words, no tussenvoesel)
     * 5. Henk Frederik de Boer (first name consists of multiple words, tussenvoegsel)
     * 6. Henk Boer - van der Meulen (tussenvoegsel within surname)
     * 7. Henk van der Plas (single tussenvoegsel, multiple words)
     * 8. Henk van der Plas - Boer (achternaam consists of mutiple words)
     * 9. Henk van der Plan - van den Achternaam (achternaam consists of multiple words and contains own tussenvoegsel of multiple words
     * 10. Dhr. van der Plas (No firstname, but gender prefix).
     *
     * There is no way to distinguish case 5 and 6 automatically, but voor/achternaam and tussenvoegsel contain different parts.
     * these case need manual inspection
     */
    public static function extractParts($name)
    {
        $nameCopy = trim($name);

        $processManually = false;
        $matchedAanhef = false;
        $matchedVoorletters = false;

        $geslacht = null;
        $voornaam = null;
        $tussenvoegsel = null;
        $achternaam = null;

        $aanhef = [
            // gender neutral
            'ir' => null, 'ing' => null, 'dr' => null, 'drs' => null, 'prof' => null,
            // male
            'meneer' => 'm', 'dhr' => 'm', 'hr' => 'm', 'de heer' => 'm',
            // female
            'mevrouw' => 'f', 'mevr' => 'f', 'mw' => 'f',
        ];

        // 1. If name starts with a titel or aanhef, strip it (but remember we did so, might be relevant for parsing rest of name).
        foreach ($aanhef as $a => $gender) {
            if (preg_match("/(^|\W){$a}\.?[\W]+/i", $nameCopy)) {
                if ($geslacht === null) {
                    $geslacht = $gender;
                }
                $nameCopy = trim(preg_replace("/(^|\W){$a}\.?[\W]+/i", '', $nameCopy));
                $matchedAanhef = true;
            }
        }

        // 2. Replace spaces in common tussenvoegsels, to make them one word
        $tussenvoegsels = ['van', 'v.', 'de', 'ter', 'den', 'van \'t', 'de la', 'da', 'van der', 'van de', 'van den', 'vd', 'v.d.', 'v.d', 'du', 'von', 'le', 'op den', 'v\/d', 'ten', 'de la', 'el', 'in \'t'];
        $transform = [];
        foreach ($tussenvoegsels as $t) {
            $transform[$t] = str_replace(' ', '**', $t);
            $nameCopy = preg_replace("/(^|[\s]){$t}[\s]/i", " {$transform[$t]} ", $nameCopy);
        }
        $nameCopy = trim($nameCopy);

        // 3. Make sure any voorletters are nicely grouped like J.R. (without spaces between them)
        if (preg_match('/^([A-Z]{1})\.?[\s]/', $nameCopy)) {
            $nameCopy = preg_replace('/([A-Z]{1})\.?[\W]/', '\1.', $nameCopy);
            $nameCopy = preg_replace('/^(([A-Z]+\.)+)/', '\1 ', $nameCopy);
            $matchedVoorletters = true;
        }

        // 4. Make sure we properly treat '-' (used when name of partner is added to achternaam)
        if ($matchedVoorletters || $matchedAanhef) {
            // We can assume everything in $nameCopy belongs to achternaam (cause initials or title already stripped)
            $nameCopy = preg_replace('/\-/', ' ##-## ', $nameCopy);
        } else {
            // A '-' before the first space might be like Jan-Willem, so '-' before first space.
            $nameCopy = preg_replace('/([\s][\w]+)( ?- ?)/', '\1 ##-## ', $nameCopy);
        }

        $parts = preg_split('/[\s]+/', $nameCopy);

        // If we have a '##-##' part, then everything from it's index-1 till the end should be considered as a single part
        if (false !== $index = array_search('##-##', $parts)) {
            $parts[$index - 1] = implode(' ', array_slice($parts, $index - 1));
            $parts = array_slice($parts, 0, $index);
        }

        // 5. Simple cases: one or two parts
        if (count($parts) === 1) {
            if ($matchedAanhef) {
                $achternaam = $parts[0];
            } else {
                $voornaam = $parts[0];
            }
        } elseif (count($parts) === 2) {
            if (in_array($parts[0], $transform)) {
                $tussenvoegsel = $parts[0];
            } else {
                $voornaam = $parts[0];
            }

            $achternaam = $parts[1];

        // 6. relatively simple case: part 2 is a tussenvoegsel
        } elseif (in_array($parts[1], $transform)) {
            $voornaam = $parts[0];
            $tussenvoegsel = $parts[1];
            $achternaam = implode(' ', array_slice($parts, 2));

        // 7. More parts, but we matched voorletters, so asume rest is lastname
        } elseif ($matchedVoorletters) {
            $voornaam = $parts[0];
            $achternaam = implode(' ', array_slice($parts, 1));

        // 8. More parts, and did not process voorletters - We can't know for sure what is first and what is last name
        } else {
            $voornaam = $name;
            $processManually = true;
        }

        // Clean up temporary formatting
        if ($voornaam !== null) {
            $voornaam = str_replace(' ##-## ', '-', $voornaam);
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
