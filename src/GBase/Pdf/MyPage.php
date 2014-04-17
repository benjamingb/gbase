<?php

namespace GBase\Pdf;

use ZendPdf\Page;

class MyPage 
{

    protected $page;

    public function __construct(Page $page)
    {
        $this->page = $page;
        return $this->page;
    }

    /**
     * 
     * @param string $text 
     * @param type $width ancho caractares antes de cortar 
     * @param type $lineHeight tamaño del salto de linea 
     * @param float $x 
     * @param float $y
     * @param string $charEncoding
     */
    public function drawTextBlock($text, $width, $lineHeight, $x, $y, $charEncoding = '')
    {
        $text  = wordwrap($text, $width);
        $token = strtok($text, "\n");

        while ($token != false) {
            $this->page->drawText($token, $x, $y, $charEncoding);
            $token = strtok("\n");
            $y -= $lineHeight;
        }
    }

}
