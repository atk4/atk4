<?php
/***********************************************************
  ..

  Reference:
  http://agiletoolkit.org/doc/ref

==ATK4===================================================
   This file is part of Agile Toolkit 4
    http://agiletoolkit.org/

   (c) 2008-2013 Agile Toolkit Limited <info@agiletoolkit.org>
   Distributed under Affero General Public License v3 and
   commercial license.

   See LICENSE or LICENSE_COM for more information
=====================================================ATK4=*/
class LoremIpsum extends AbstractView {
    private $message;
    private $paragraphs=3;
    private $words=200;

    function create_greeking($words, $min_words = 3, $max_words = 10) {
        $punctuation = array(". ", ". ", ". ", ". ", ". ", ". ", ". ", ". ", "... ", "! ", "? ");

        $dictionary = array("abbas", "abdo", "abico", "abigo", "abluo", "accumsan",
                "acsi", "ad", "adipiscing", "aliquam", "aliquip", "amet", "antehabeo",
                "appellatio", "aptent", "at", "augue", "autem", "bene", "blandit",
                "brevitas", "caecus", "camur", "capto", "causa", "cogo", "comis",
                "commodo", "commoveo", "consectetuer", "consequat", "conventio", "cui",
                "damnum", "decet", "defui", "diam", "dignissim", "distineo", "dolor",
                "dolore", "dolus", "duis", "ea", "eligo", "elit", "enim", "erat",
                "eros", "esca", "esse", "et", "eu", "euismod", "eum", "ex", "exerci",
                "exputo", "facilisi", "facilisis", "fere", "feugiat", "gemino",
                "genitus", "gilvus", "gravis", "haero", "hendrerit", "hos", "huic",
                "humo", "iaceo", "ibidem", "ideo", "ille", "illum", "immitto",
                "importunus", "imputo", "in", "incassum", "inhibeo", "interdico",
                "iriure", "iusto", "iustum", "jugis", "jumentum", "jus", "laoreet",
                "lenis", "letalis", "lobortis", "loquor", "lucidus", "luctus", "ludus",
                "luptatum", "macto", "magna", "mauris", "melior", "metuo", "meus",
                "minim", "modo", "molior", "mos", "natu", "neo", "neque", "nibh",
                "nimis", "nisl", "nobis", "nostrud", "nulla", "nunc", "nutus", "obruo",
                "occuro", "odio", "olim", "oppeto", "os", "pagus", "pala", "paratus",
                "patria", "paulatim", "pecus", "persto", "pertineo", "plaga", "pneum",
                "populus", "praemitto", "praesent", "premo", "probo", "proprius",
                "quadrum", "quae", "qui", "quia", "quibus", "quidem", "quidne", "quis",
                "ratis", "refero", "refoveo", "roto", "rusticus", "saepius",
                "sagaciter", "saluto", "scisco", "secundum", "sed", "si", "similis",
                "singularis", "sino", "sit", "sudo", "suscipere", "suscipit", "tamen",
                "tation", "te", "tego", "tincidunt", "torqueo", "tum", "turpis",
                "typicus", "ulciscor", "ullamcorper", "usitas", "ut", "utinam",
                "utrum", "uxor", "valde", "valetudo", "validus", "vel", "velit",
                "veniam", "venio", "vereor", "vero", "verto", "vicis", "vindico",
                "virtus", "voco", "volutpat", "vulpes", "vulputate", "wisi", "ymo",
                "zelus");

        $greeking = "";

        while ($words > 0) {
            $sentence_length = rand($min_words ,$max_words);

            $greeking .= ucfirst($dictionary[array_rand($dictionary)]);
            for ($i = 1; $i < $sentence_length; $i++) {
                $greeking .= " " . $dictionary[array_rand($dictionary)];
            }

            $greeking .= $punctuation[array_rand($punctuation)];;
            $words -= $sentence_length;
        }

        return $greeking;
    }

    function setLength($paragraphs,$words){
        $this->paragraphs=$paragraphs;
        $this->words=$words;
        return $this;
    }
    function render(){
        $this->output('<div id="'.$this->name.'">');
        for($x=0;$x<$this->paragraphs;$x++){
            $this->output('<p>');
            $this->output($this->create_greeking($this->words));
            $this->output('</p>');
        }
        $this->output('</div>');
    }
}
