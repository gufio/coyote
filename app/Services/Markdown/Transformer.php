<?php

namespace Coyote\Services\Markdown;

use Coyote\Services\Parser\Parsers\Parser;

class Transformer extends Parser
{
    public function transform($text)
    {
        // na poczatek zmiana adresow, wszedzie, w calym tekscie
        $text = $this->url($text);
        // pozbywamy sie starych linkow, rowniez wszedzie
        $text = $this->links($text);

        // znaczniki <plain> nie maja racji bytu
        $text = $this->removePlain($text);
        // poprawa tagow <code>
        $text = $this->fixCodeTag($text);

        // w <code> nie beda dokonywane dalsze konwersje
        $text = $this->hashBlock($text, 'code');

        $text = $this->fixDeprecatedApostrophes($text);

        // @todo usunac znacznik <div> (tylko poza <code>!) albo zezwolic na jego korzystanie
        // @todo usunac z tekstu `<code="język"></code>` na \```jezyl\```
        // @todo usunac <tt>

        $text = $this->unhash($text);

        $text = $this->removeCodeTag($text);

        return $text;
    }

    private function url(string $text): string
    {
        $text = str_replace('forum.4programmers.net', '4programmers.net/Forum', $text);

        // to musi byc przed kolejna linia kodu
        $text = preg_replace('~4programmers\.net\/Forum\/viewtopic\.php\?p\=(\d+)\&~', '4programmers.net/Forum/$1?', $text);
        $text = preg_replace('~4programmers\.net\/Forum\/viewtopic\.php\?p\=(\d+)~', '4programmers.net/Forum/$1', $text);

        return $text;
    }

    private function links(string $text): string
    {
        $text = preg_replace(
            "#<url>([a-z]+?://)([][()^{}%$0-9a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ.,?!%*_\#:;~\\&$@/=+-]+)</url>#si",
            "<a href=\"$1$2\">$1$2</a>",
            $text
        );

        $text = preg_replace(
            "#<url=([a-z]+?://)([][()^{}%$0-9a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ.,?!%*_\#:;~\\&$@/=+-]+)>(.+)</url>#Usi",
            "<a href=\"$1$2\">$3</a>",
            $text
        );

        $text = preg_replace(
            "#<url=\"([a-z]+?://)([][()^{}%$0-9a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ.,?!%*_\#:;~\\&$@/=+-]+)\">(.+)</url>#Usi",
            "<a href=\"$1$2\">$3</a>",
            $text
        );

        $text = preg_replace("#<email>([a-z0-9\-_.]+?@[a-z0-9\-_.]+?)</email>#si", "$1$2", $text);

        $text = preg_replace(
            "#<image>([a-z]+?://)([a-z0-9\-\.,\?!%\*\[\]_\#:;~\\&$@\/=\+\^{}() ]+)</image>#si",
            "![user image]($1$2)",
            $text
        );

        $text = preg_replace(
            "#<wiki>([^<]*)</wiki>#si",
            "<a href=\"http://pl.wikipedia.org/wiki/$1\">$1</a>",
            $text
        );

        return $text;
    }

    private function removePlain($text)
    {
        // <plain> zamieniamy na <code>. stare znaczniki (np. '') ktore znajduja sie w <plain>, nie beda
        // zamienione na markdown
        return str_replace(['<plain>', '</plain>'], ['<code>', '</code>'], $text);
    }

    private function fixCodeTag($text)
    {
        $callable = function ($matches) {
            if (empty($matches[1])) {
                return '<code>';
            }

            return '<code class="' . str_replace('c++', 'cpp', strtolower($matches[1])) . '">';
        };

        $text = preg_replace_callback(
            '|<code(?:(?:=([a-z\d#-]+))?(?::((?:[a-z]+\|)*[a-z]+))?)?>|is',
            $callable,
            $text
        );

        $text = preg_replace_callback(
            '|<code="([a-z\+]+)">|is',
            $callable,
            $text
        );

        $syntaxTags = 'php|delphi|cpp|asm';

        /* zastapienie starych znacznikow kolorowania skladni - nowymi */
        $text = preg_replace("#<({$syntaxTags}*)>(.*?)</({$syntaxTags}*)>#is", '<code class="$1">$2</code>', $text);

        return $text;
    }

    private function fixDeprecatedApostrophes(string $text): string
    {
        $lines = $this->splitLineBreaks($text);

        foreach ($lines as &$line) {
            $searchFor = "''";

            while (($start = strpos($line, $searchFor)) !== false) {
                $end = strpos($line, $searchFor, $start + 1);

                if ($end === false) {
                    break;
                } else {
                    $line = substr_replace($line, '`', $start, 2);
                    $line = substr_replace($line, '`', $end - 1, 2);
                }
            }

//            $line = str_replace(["`''`", "''`", '``'], ['`', '`', '`'], $line);
        }

        return $this->joinLineBreaks($lines);
    }

    private function removeCodeTag(string $text): string
    {
        $text = str_replace(['`<code><code></code>`', '`<code></code></code>`'], ['`<code>`', '`</code>`'], $text);
        $lines = $this->splitLineBreaks($text);

        foreach ($lines as &$line) {
            if ($line === '<code>' || $line === '</code>') {
                $line = '```';
                continue;
            }

            $offset = 0;
            // jezeli <code> i </code> znajduja sie w tej samej linii...
            while (($start = strpos($line, '<code>', $offset)) !== false && ($end = strrpos($line, '</code>', $offset)) !== false) {
                // jezeli sa backticki na poczaktu lub na koncu - nie robimy nic
                $len = strlen('<code>');
                $offset = min(strlen($line), $offset + $start + 1);

                if ($start > 0 && $line[$start - 1] === '`' && $line[$start + $len] === '`') {
                    continue;
                }

                // jezeli pomeidzy <code> a </code> znajduje sie jeszcze jeden zancznik <code ...
                // brzydki hack...
                if ((strpos($line, '<code>', $start + 1) !== false) && ($firstOccur = strpos($line, '</code>', $offset)) !== false) {
                    $end = min($end, $firstOccur);
                }

                $line = substr_replace($line, '`', $start, $len);
                $line = substr_replace($line, '`', $end - $len + 1, $len + 1);
            }

            if (($start = preg_match('|<code class="([a-z\+]+)">|i', $line, $match, PREG_OFFSET_CAPTURE)) === 1) {
                $start = $match[0][1];
                $found = $match[0][0];
                $lang  = $match[1][0];

                if ($start > 1) {
                    continue;
                }

                if ('' === substr($line, strlen($found))) {
                    $line = '```' . $lang;
                }
            }
        }

        return $this->joinLineBreaks($lines);
    }

    private function splitLineBreaks(string $text): array
    {
        return explode("\n", $text);
    }

    private function joinLineBreaks(array $lines): string
    {
        return implode("\n", $lines);
    }
}