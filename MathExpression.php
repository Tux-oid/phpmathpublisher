<?php
/***************************************************************************
 *   copyright            : (C) 2005 by Pascal Brachet - France            *
 *   pbrachet_NOSPAM_xm1math.net (replace _NOSPAM_ by @)                   *
 *   http://www.xm1math.net/phpmathpublisher/                              *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 ***************************************************************************/

namespace RL\PhpMathPublisher;

/**
 * \RL\PhpMathPublisher\MathExpression
 *
 * @author Pascal Brachet <pbrachet@xm1math.net>
 * @author Peter Vasilevsky <tuxoiduser@gmail.com> a.k.a. Tux-oid
 * @license GPLv2
 */
class MathExpression extends Expression
{
    /**
     * @var array
     */
    public $nodes;

    /**
     * Constructor
     */
    public function __construct($exp, $helper)
    {
        $this->helper = $helper;
        $this->text = "&$";
        $this->nodes = $exp;
        $this->nodes = $this->parse();
    }

    /**
     * @return array
     */
    public function parse()
    {
        if (count($this->nodes) <= 3) {
            return $this->nodes;
        }
        $ret = array();
        $parenthesiss = array();
        for ($i = 0; $i < count($this->nodes); $i++) {
            if ($this->nodes[$i]->text == '(' || $this->nodes[$i]->text == '{') {
                array_push($parenthesiss, $i);
            } elseif ($this->nodes[$i]->text == ')' || $this->nodes[$i]->text == '}') {
                $pos = array_pop($parenthesiss);
                if (count($parenthesiss) == 0) {
                    $sub = array_slice($this->nodes, $pos + 1, $i - $pos - 1);
                    if ($this->nodes[$i]->text == ')') {
                        $ret[] = new MathExpression(array(
                            new TextExpression("(", $this->helper),
                            new MathExpression($sub, $this->helper),
                            new TextExpression(")", $this->helper)
                        ), $this->helper);
                    } else {
                        $ret[] = new MathExpression($sub, $this->helper);
                    }
                }
            } elseif (count($parenthesiss) == 0) {
                $ret[] = $this->nodes[$i];
            }
        }
        $ret = $this->handleFunction($ret, 'sqrt', 1);
        $ret = $this->handleFunction($ret, 'vec', 1);
        $ret = $this->handleFunction($ret, 'overline', 1);
        $ret = $this->handleFunction($ret, 'underline', 1);
        $ret = $this->handleFunction($ret, 'hat', 1);
        $ret = $this->handleFunction($ret, 'int', 3);
        $ret = $this->handleFunction($ret, 'doubleint', 3);
        $ret = $this->handleFunction($ret, 'tripleint', 3);
        $ret = $this->handleFunction($ret, 'oint', 3);
        $ret = $this->handleFunction($ret, 'prod', 3);
        $ret = $this->handleFunction($ret, 'sum', 3);
        $ret = $this->handleFunction($ret, 'bigcup', 3);
        $ret = $this->handleFunction($ret, 'bigcap', 3);
        $ret = $this->handleFunction($ret, 'delim', 3);
        $ret = $this->handleFunction($ret, 'lim', 2);
        $ret = $this->handleFunction($ret, 'root', 2);
        $ret = $this->handleFunction($ret, 'matrix', 3);
        $ret = $this->handleFunction($ret, 'tabular', 3);

        $ret = $this->handleOperation($ret, '^');
        $ret = $this->handleOperation($ret, 'over');
        $ret = $this->handleOperation($ret, '_');
        $ret = $this->handleOperation($ret, 'under');
        $ret = $this->handleOperation($ret, '*');
        $ret = $this->handleOperation($ret, '/');
        $ret = $this->handleOperation($ret, '+');
        $ret = $this->handleOperation($ret, '-');

        return $ret;
    }

    /**
     * @param array $nodes
     * @param $operation
     * @return array
     */
    public function handleOperation(array $nodes, $operation)
    {
        do {
            $change = false;
            if (count($nodes) <= 3) {
                return $nodes;
            }
            $ret = array();
            for ($i = 0; $i < count($nodes); $i++) {
                if (!$change && $i < count($nodes) - 2 && $nodes[$i + 1]->text == $operation) {
                    $ret[] = new MathExpression(array($nodes[$i], $nodes[$i + 1], $nodes[$i + 2]), $this->helper);
                    $i += 2;
                    $change = true;
                } else {
                    $ret[] = $nodes[$i];
                }
            }
            $nodes = $ret;
        } while ($change);

        return $ret;
    }

    /**
     * @param array $nodes
     * @param $function
     * @param $argumentsCount
     * @return array
     */
    public function handleFunction(array $nodes, $function, $argumentsCount)
    {
        if (count($nodes) <= $argumentsCount + 1) {
            return $nodes;
        }
        $ret = array();
        for ($i = 0; $i < count($nodes); $i++) {
            if ($i < count($nodes) - $argumentsCount && $nodes[$i]->text == $function) {
                $a = array();
                for ($j = $i; $j <= $i + $argumentsCount; $j++) {
                    $a[] = $nodes[$j];
                }
                $ret[] = new MathExpression($a, $this->helper);
                $i += $argumentsCount;
            } else {
                $ret[] = $nodes[$i];
            }
        }

        return $ret;
    }


    /**
     * @param $size
     */
    public function draw($size)
    {
        switch (count($this->nodes)) {
            case 1:
                $this->nodes[0]->draw($size);
                $this->image = $this->nodes[0]->image;
                $this->verticalBased = $this->nodes[0]->verticalBased;
                break;
            case 2:
                switch ($this->nodes[0]->text) {
                    case 'sqrt':
                        $this->drawSqrt($size);
                        break;
                    case 'vec':
                        $this->drawVector($size);
                        break;
                    case 'overline':
                        $this->drawOverLine($size);
                        break;
                    case 'underline':
                        $this->drawUnderline($size);
                        break;
                    case 'hat':
                        $this->drawHat($size);
                        break;
                    default:
                        $this->drawExpression($size);
                        break;
                }
                break;
            case 3:
                if ($this->nodes[0]->text == "lim") {
                    $this->drawLimit($size);
                } elseif ($this->nodes[0]->text == "root") {
                    $this->drawRoot($size);
                } else {
                    switch ($this->nodes[1]->text) {
                        case '/':
                            $this->drawFraction($size);
                            break;
                        case '^':
                            $this->drawExponent($size);
                            break;
                        case 'over':
                            $this->drawTop($size);
                            break;
                        case '_':
                            $this->drawIndex($size);
                            break;
                        case 'under':
                            $this->draw_bottom($size);
                            break;
                        default:
                            $this->drawExpression($size);
                            break;
                    }
                }
                break;
            case 4:
                switch ($this->nodes[0]->text) {
                    case 'int':
                        $this->drawLargestOperator($size, '_integrale');
                        break;
                    case 'doubleint':
                        $this->drawLargestOperator($size, '_dintegrale');
                        break;
                    case 'tripleint':
                        $this->drawLargestOperator($size, '_tintegrale');
                        break;
                    case 'oint':
                        $this->drawLargestOperator($size, '_ointegrale');
                        break;
                    case 'sum':
                        $this->drawLargestOperator($size, '_somme');
                        break;
                    case 'prod':
                        $this->drawLargestOperator($size, '_produit');
                        break;
                    case 'bigcap':
                        $this->drawLargestOperator($size, '_intersection');
                        break;
                    case 'bigcup':
                        $this->drawLargestOperator($size, '_reunion');
                        break;
                    case 'delim':
                        $this->drawDelimiter($size);
                        break;
                    case 'matrix':
                        $this->drawMatrix($size);
                        break;
                    case 'tabular':
                        $this->drawTable($size);
                        break;
                    default:
                        $this->drawExpression($size);
                        break;
                }
                break;
            default:
                $this->drawExpression($size);
                break;
        }
    }

    /**
     * @param $size
     */
    public function drawExpression($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $width = 1;
        $height = 1;
        $top = 1;
        $bottom = 1;
        for ($i = 0; $i < count($this->nodes); $i++) {
            if ($this->nodes[$i]->text != '(' && $this->nodes[$i]->text != ')') {
                $this->nodes[$i]->draw($size);
                $img[$i] = $this->nodes[$i]->image;
                $base[$i] = $this->nodes[$i]->verticalBased;
                $top = max($base[$i], $top);
                $bottom = max(imagesy($img[$i]) - $base[$i], $bottom);
            }
        }
        $height = $top + $bottom;
        $paro = $this->helper->parenthesis(max($top, $bottom) * 2, "(");
        $parf = $this->helper->parenthesis(max($top, $bottom) * 2, ")");
        for ($i = 0; $i < count($this->nodes); $i++) {
            if (!isset($img[$i])) {
                if ($this->nodes[$i]->text == "(") {
                    $img[$i] = $paro;
                } else {
                    $img[$i] = $parf;
                }
                $top = max(imagesy($img[$i]) / 2, $top);
                $base[$i] = imagesy($img[$i]) / 2;
                $bottom = max(imagesy($img[$i]) - $base[$i], $bottom);
                $height = max(imagesy($img[$i]), $height);
            }
            $width += imagesx($img[$i]);
        }
        $this->verticalBased = $top;
        $result = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($result, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($result, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($result, $white);
        }
        ImageFilledRectangle($result, 0, 0, $width - 1, $height - 1, $white);
        $pos = 0;
        for ($i = 0; $i < count($img); $i++) {
            if (isset($img[$i])) {
                ImageCopy($result, $img[$i], $pos, $top - $base[$i], 0, 0, imagesx($img[$i]), imagesy($img[$i]));
                $pos += imagesx($img[$i]);
            }
        }
        $this->image = $result;
    }

    /**
     * @param $size
     */
    public function drawFraction($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $this->nodes[0]->draw($size * 0.9);
        $img1 = $this->nodes[0]->image;
        $base1 = $this->nodes[0]->verticalBased;
        $this->nodes[2]->draw($size * 0.9);
        $img2 = $this->nodes[2]->image;
        $base2 = $this->nodes[2]->verticalBased;
        $height1 = imagesy($img1);
        $height2 = imagesy($img2);
        $width1 = imagesx($img1);
        $width2 = imagesx($img2);
        $width = max($width1, $width2);
        $height = $height1 + $height2 + 4;
        $result = ImageCreate(max($width + 5, 1), max($height, 1));
        $black = ImageColorAllocate($result, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($result, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($result, $white);
        }
        $this->verticalBased = $height1 + 2;
        ImageFilledRectangle($result, 0, 0, $width + 4, $height - 1, $white);
        ImageCopy($result, $img1, ($width - $width1) / 2, 0, 0, 0, $width1, $height1);
        imageline($result, 0, $this->verticalBased, $width, $this->verticalBased, $black);
        ImageCopy($result, $img2, ($width - $width2) / 2, $height1 + 4, 0, 0, $width2, $height2);
        $this->image = $result;
    }

    /**
     * @param $size
     */
    public function drawExponent($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $this->nodes[0]->draw($size);
        $img1 = $this->nodes[0]->image;
        $base1 = $this->nodes[0]->verticalBased;
        $this->nodes[2]->draw($size * 0.8);
        $img2 = $this->nodes[2]->image;
        $base2 = $this->nodes[2]->verticalBased;
        $height1 = imagesy($img1);
        $height2 = imagesy($img2);
        $width1 = imagesx($img1);
        $width2 = imagesx($img2);
        $width = $width1 + $width2;
        if ($height1 >= $height2) {
            $height = ceil($height2 / 2 + $height1);
            $this->verticalBased = $height2 / 2 + $base1;
            $result = ImageCreate(max($width, 1), max($height, 1));
            $black = ImageColorAllocate($result, $fontR, $fontG, $fontB);
            $white = ImageColorAllocate($result, $backR, $backG, $backB);
            if ($transparent) {
                $white = imagecolortransparent($result, $white);
            }
            ImageFilledRectangle($result, 0, 0, $width - 1, $height - 1, $white);
            ImageCopy($result, $img1, 0, ceil($height2 / 2), 0, 0, $width1, $height1);
            ImageCopy($result, $img2, $width1, 0, 0, 0, $width2, $height2);
        } else {
            $height = ceil($height1 / 2 + $height2);
            $this->verticalBased = $height2 - $base1 + $height1 / 2;
            $result = ImageCreate(max($width, 1), max($height, 1));
            $black = ImageColorAllocate($result, $fontR, $fontG, $fontB);
            $white = ImageColorAllocate($result, $backR, $backG, $backB);
            if ($transparent) {
                $white = imagecolortransparent($result, $white);
            }
            ImageFilledRectangle($result, 0, 0, $width - 1, $height - 1, $white);
            ImageCopy($result, $img1, 0, ceil($height2 - $height1 / 2), 0, 0, $width1, $height1);
            ImageCopy($result, $img2, $width1, 0, 0, 0, $width2, $height2);
        }
        $this->image = $result;
    }

    /**
     * @param $size
     */
    public function drawIndex($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $this->nodes[0]->draw($size);
        $img1 = $this->nodes[0]->image;
        $base1 = $this->nodes[0]->verticalBased;
        $this->nodes[2]->draw($size * 0.8);
        $img2 = $this->nodes[2]->image;
        $base2 = $this->nodes[2]->verticalBased;
        $height1 = imagesy($img1);
        $height2 = imagesy($img2);
        $width1 = imagesx($img1);
        $width2 = imagesx($img2);
        $width = $width1 + $width2;
        if ($height1 >= $height2) {
            $height = ceil($height2 / 2 + $height1);
            $this->verticalBased = $base1;
            $result = ImageCreate(max($width, 1), max($height, 1));
            $black = ImageColorAllocate($result, $fontR, $fontG, $fontB);
            $white = ImageColorAllocate($result, $backR, $backG, $backB);
            if ($transparent) {
                $white = imagecolortransparent($result, $white);
            }
            ImageFilledRectangle($result, 0, 0, $width - 1, $height - 1, $white);
            ImageCopy($result, $img1, 0, 0, 0, 0, $width1, $height1);
            ImageCopy($result, $img2, $width1, ceil($height1 - $height2 / 2), 0, 0, $width2, $height2);
        } else {
            $height = ceil($height1 / 2 + $height2);
            $this->verticalBased = $base1;
            $result = ImageCreate(max($width, 1), max($height, 1));
            $black = ImageColorAllocate($result, $fontR, $fontG, $fontB);
            $white = ImageColorAllocate($result, $backR, $backG, $backB);
            if ($transparent) {
                $white = imagecolortransparent($result, $white);
            }
            ImageFilledRectangle($result, 0, 0, $width - 1, $height - 1, $white);
            ImageCopy($result, $img1, 0, 0, 0, 0, $width1, $height1);
            ImageCopy($result, $img2, $width1, ceil($height1 / 2), 0, 0, $width2, $height2);
        }
        $this->image = $result;
    }

    /**
     * @param $size
     */
    public function drawSqrt($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $this->nodes[1]->draw($size);
        $imgExp = $this->nodes[1]->image;
        $baseExp = $this->nodes[1]->verticalBased;
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);

        $imgrac = $this->helper->displaySymbol("_racine", $heightExp + 2);
        $widthrac = imagesx($imgrac);
        $heightrac = imagesy($imgrac);
        $baserac = $heightrac / 2;

        $width = $widthrac + $widthExp;
        $height = max($heightExp, $heightrac);
        $result = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($result, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($result, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($result, $white);
        }
        ImageFilledRectangle($result, 0, 0, $width - 1, $height - 1, $white);
        ImageCopy($result, $imgrac, 0, 0, 0, 0, $widthrac, $heightrac);
        ImageCopy($result, $imgExp, $widthrac, $height - $heightExp, 0, 0, $widthExp, $heightExp);
        imagesetthickness($result, 1);
        imageline($result, $widthrac - 2, 2, $widthrac + $widthExp + 2, 2, $black);
        $this->verticalBased = $height - $heightExp + $baseExp;
        $this->image = $result;
    }

    /**
     * @param $size
     */
    public function drawRoot($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $this->nodes[1]->draw($size * 0.6);
        $imgRoot = $this->nodes[1]->image;
        $baseRoot = $this->nodes[1]->verticalBased;
        $widthRoot = imagesx($imgRoot);
        $heightRoot = imagesy($imgRoot);

        $this->nodes[2]->draw($size);
        $imgExp = $this->nodes[2]->image;
        $baseExp = $this->nodes[2]->verticalBased;
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);

        $imgRac = $this->helper->displaySymbol("_racine", $heightExp + 2);
        $widthRac = imagesx($imgRac);
        $heightRac = imagesy($imgRac);
        $baseRac = $heightRac / 2;

        $width = $widthRac + $widthExp;
        $height = max($heightExp, $heightRac);
        $result = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($result, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($result, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($result, $white);
        }
        ImageFilledRectangle($result, 0, 0, $width - 1, $height - 1, $white);
        ImageCopy($result, $imgRac, 0, 0, 0, 0, $widthRac, $heightRac);
        ImageCopy($result, $imgExp, $widthRac, $height - $heightExp, 0, 0, $widthExp, $heightExp);
        imagesetthickness($result, 1);
        imageline($result, $widthRac - 2, 2, $widthRac + $widthExp + 2, 2, $black);
        ImageCopy($result, $imgRoot, 0, 0, 0, 0, $widthRoot, $heightRoot);
        $this->verticalBased = $height - $heightExp + $baseExp;
        $this->image = $result;
    }

    /**
     * @param $size
     * @param $character
     */
    public function drawLargestOperator($size, $character)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $this->nodes[1]->draw($size * 0.8);
        $img1 = $this->nodes[1]->image;
        $base1 = $this->nodes[1]->verticalBased;
        $this->nodes[2]->draw($size * 0.8);
        $img2 = $this->nodes[2]->image;
        $base2 = $this->nodes[2]->verticalBased;
        $this->nodes[3]->draw($size);
        $imgExp = $this->nodes[3]->image;
        $baseExp = $this->nodes[3]->verticalBased;
        //borneinf
        $width1 = imagesx($img1);
        $height1 = imagesy($img1);
        //bornesup
        $width2 = imagesx($img2);
        $height2 = imagesy($img2);
        //expression
        $heightExp = imagesy($imgExp);
        $widthExp = imagesx($imgExp);
        //character
        $imgSymbol = $this->helper->displaySymbol($character, $baseExp * 1.8); //max($baseExp,$heightExp-$baseExp)*2);
        $widthSymbol = imagesx($imgSymbol);
        $heightSymbol = imagesy($imgSymbol);
        $baseSymbol = $heightSymbol / 2;

        $heightLeft = $heightSymbol + $height1 + $height2;
        $widthLeft = max($widthSymbol, $width1, $width2);
        $imgLeft = ImageCreate(max($widthLeft, 1), max($heightLeft, 1));
        $black = ImageColorAllocate($imgLeft, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgLeft, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgLeft, $white);
        }
        ImageFilledRectangle($imgLeft, 0, 0, $widthLeft - 1, $heightLeft - 1, $white);
        ImageCopy($imgLeft, $imgSymbol, ($widthLeft - $widthSymbol) / 2, $height2, 0, 0, $widthSymbol, $heightSymbol);
        ImageCopy($imgLeft, $img2, ($widthLeft - $width2) / 2, 0, 0, 0, $width2, $height2);
        ImageCopy($imgLeft, $img1, ($widthLeft - $width1) / 2, $height2 + $heightSymbol, 0, 0, $width1, $height1);
        $imgFin = $this->helper->alinement2($imgLeft, $baseSymbol + $height2, $imgExp, $baseExp);
        $this->image = $imgFin;
        $this->verticalBased = max($baseSymbol + $height2, $baseExp + $height2);
    }

    /**
     * @param $size
     */
    public function drawTop($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $this->nodes[2]->draw($size * 0.8);
        $imgSup = $this->nodes[2]->image;
        $baseSup = $this->nodes[2]->verticalBased;
        $this->nodes[0]->draw($size);
        $imgExp = $this->nodes[0]->image;
        $baseExp = $this->nodes[0]->verticalBased;
        //expression
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);
        //bornesup
        $widthSup = imagesx($imgSup);
        $heightSup = imagesy($imgSup);
        //fin
        $height = $heightExp + $heightSup;
        $width = max($widthSup, $widthExp) + ceil($size / 8);
        $imgFin = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $width - 1, $height - 1, $white);
        ImageCopy($imgFin, $imgSup, ($width - $widthSup) / 2, 0, 0, 0, $widthSup, $heightSup);
        ImageCopy($imgFin, $imgExp, ($width - $widthExp) / 2, $heightSup, 0, 0, $widthExp, $heightExp);
        $this->image = $imgFin;
        $this->verticalBased = $baseExp + $heightSup;
    }

    /**
     * @param $size
     */
    public function draw_bottom($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $this->nodes[2]->draw($size * 0.8);
        $imgInf = $this->nodes[2]->image;
        $baseInf = $this->nodes[2]->verticalBased;
        $this->nodes[0]->draw($size);
        $imgExp = $this->nodes[0]->image;
        $baseExp = $this->nodes[0]->verticalBased;
        //expression
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);
        //borneinf
        $widthInf = imagesx($imgInf);
        $heightInf = imagesy($imgInf);
        //fin
        $height = $heightExp + $heightInf;
        $width = max($widthInf, $widthExp) + ceil($size / 8);
        $imgFin = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $width - 1, $height - 1, $white);
        ImageCopy($imgFin, $imgExp, ($width - $widthExp) / 2, 0, 0, 0, $widthExp, $heightExp);
        ImageCopy($imgFin, $imgInf, ($width - $widthInf) / 2, $heightExp, 0, 0, $widthInf, $heightInf);
        $this->image = $imgFin;
        $this->verticalBased = $baseExp;
    }

    /**
     * @param $size
     */
    public function drawMatrix($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $padding = 8;
        $nbLine = $this->nodes[1]->nodes[0]->text;
        $nbColumn = $this->nodes[2]->nodes[0]->text;
        $widthCase = 0;
        $heightCase = 0;

        for ($line = 0; $line < $nbLine; $line++) {
            $heightLine[$line] = 0;
            $topLine[$line] = 0;
        }
        for ($col = 0; $col < $nbColumn; $col++) {
            $widthColumn[$col] = 0;
        }
        $i = 0;
        for ($line = 0; $line < $nbLine; $line++) {
            for ($col = 0; $col < $nbColumn; $col++) {
                if ($i < count($this->nodes[3]->nodes)) {
                    $this->nodes[3]->nodes[$i]->draw($size * 0.9);
                    $img[$i] = $this->nodes[3]->nodes[$i]->image;
                    $base[$i] = $this->nodes[3]->nodes[$i]->verticalBased;
                    $topLine[$line] = max($base[$i], $topLine[$line]);
                    $width[$i] = imagesx($img[$i]);
                    $height[$i] = imagesy($img[$i]);
                    $heightLine[$line] = max($heightLine[$line], $height[$i]);
                    $widthColumn[$col] = max($widthColumn[$col], $width[$i]);
                }
                $i++;
            }
        }

        $heightFin = 0;
        $widthFin = 0;
        for ($line = 0; $line < $nbLine; $line++) {
            $heightFin += $heightLine[$line] + $padding;
        }
        for ($col = 0; $col < $nbColumn; $col++) {
            $widthFin += $widthColumn[$col] + $padding;
        }
        $heightFin -= $padding;
        $widthFin -= $padding;
        $imgFin = ImageCreate(max($widthFin, 1), max($heightFin, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $widthFin - 1, $heightFin - 1, $white);
        $i = 0;
        $h = $padding / 2 - 1;
        for ($line = 0; $line < $nbLine; $line++) {
            $l = $padding / 2 - 1;
            for ($col = 0; $col < $nbColumn; $col++) {
                if ($i < count($this->nodes[3]->nodes)) {
                    ImageCopy(
                        $imgFin,
                        $img[$i],
                        $l + ceil($widthColumn[$col] - $width[$i]) / 2,
                        $h + $topLine[$line] - $base[$i],
                        0,
                        0,
                        $width[$i],
                        $height[$i]
                    );
                    //ImageRectangle($imgFin,$l,$h,$l+$widthColumn[$col],$h+$heightLine[$line],$black);
                }
                $l += $widthColumn[$col] + $padding;
                $i++;
            }
            $h += $heightLine[$line] + $padding;
        }
        //ImageRectangle($imgFin,0,0,$widthFin-1,$heightFin-1,$black);
        $this->image = $imgFin;
        $this->verticalBased = imagesy($imgFin) / 2;
    }

    /**
     * @param $size
     */
    public function drawTable($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $padding = 8;
        $typeLine = $this->nodes[1]->nodes[0]->text;
        $typeColumn = $this->nodes[2]->nodes[0]->text;
        $nbLine = strlen($typeLine) - 1;
        $nbColumn = strlen($typeColumn) - 1;
        $widthCase = 0;
        $heightCase = 0;

        for ($line = 0; $line < $nbLine; $line++) {
            $heightLine[$line] = 0;
            $topLine[$line] = 0;
        }
        for ($col = 0; $col < $nbColumn; $col++) {
            $widthColumn[$col] = 0;
        }
        $i = 0;
        for ($line = 0; $line < $nbLine; $line++) {
            for ($col = 0; $col < $nbColumn; $col++) {
                if ($i < count($this->nodes[3]->nodes)) {
                    $this->nodes[3]->nodes[$i]->draw($size * 0.9);
                    $img[$i] = $this->nodes[3]->nodes[$i]->image;
                    $base[$i] = $this->nodes[3]->nodes[$i]->verticalBased;
                    $topLine[$line] = max($base[$i], $topLine[$line]);
                    $width[$i] = imagesx($img[$i]);
                    $height[$i] = imagesy($img[$i]);
                    $heightLine[$line] = max($heightLine[$line], $height[$i]);
                    $widthColumn[$col] = max($widthColumn[$col], $width[$i]);
                }
                $i++;
            }
        }

        $heightFin = 0;
        $widthFin = 0;
        for ($line = 0; $line < $nbLine; $line++) {
            $heightFin += $heightLine[$line] + $padding;
        }
        for ($col = 0; $col < $nbColumn; $col++) {
            $widthFin += $widthColumn[$col] + $padding;
        }
        $imgFin = ImageCreate(max($widthFin, 1), max($heightFin, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $widthFin - 1, $heightFin - 1, $white);
        $i = 0;
        $h = $padding / 2 - 1;
        if (substr($typeLine, 0, 1) == "1") {
            ImageLine($imgFin, 0, 0, $widthFin - 1, 0, $black);
        }
        for ($line = 0; $line < $nbLine; $line++) {
            $l = $padding / 2 - 1;
            if (substr($typeColumn, 0, 1) == "1") {
                ImageLine($imgFin, 0, $h - $padding / 2, 0, $h + $heightLine[$line] + $padding / 2, $black);
            }
            for ($col = 0; $col < $nbColumn; $col++) {
                if ($i < count($this->nodes[3]->nodes)) {
                    ImageCopy(
                        $imgFin,
                        $img[$i],
                        $l + ceil($widthColumn[$col] - $width[$i]) / 2,
                        $h + $topLine[$line] - $base[$i],
                        0,
                        0,
                        $width[$i],
                        $height[$i]
                    );
                    if (substr($typeColumn, $col + 1, 1) == "1") {
                        ImageLine(
                            $imgFin,
                            $l + $widthColumn[$col] + $padding / 2,
                            $h - $padding / 2,
                            $l + $widthColumn[$col] + $padding / 2,
                            $h + $heightLine[$line] + $padding / 2,
                            $black
                        );
                    }
                }
                $l += $widthColumn[$col] + $padding;
                $i++;
            }
            if (substr($typeLine, $line + 1, 1) == "1") {
                ImageLine(
                    $imgFin,
                    0,
                    $h + $heightLine[$line] + $padding / 2,
                    $widthFin - 1,
                    $h + $heightLine[$line] + $padding / 2,
                    $black
                );
            }
            $h += $heightLine[$line] + $padding;
        }
        $this->image = $imgFin;
        $this->verticalBased = imagesy($imgFin) / 2;
    }

    /**
     * @param $size
     */
    public function drawVector($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        //expression
        $this->nodes[1]->draw($size);
        $imgExp = $this->nodes[1]->image;
        $baseExp = $this->nodes[1]->verticalBased;
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);
        //fleche
        $imgSup = $this->helper->displaySymbol("right", 16);
        $widthSup = imagesx($imgSup);
        $heightSup = imagesy($imgSup);
        //fin
        $height = $heightExp + $heightSup;
        $width = $widthExp;
        $imgFin = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $width - 1, $height - 1, $white);
        ImageCopy($imgFin, $imgSup, $width - 6, 0, $widthSup - 6, 0, $widthSup, $heightSup);
        imagesetthickness($imgFin, 1);
        imageline($imgFin, 0, 6, $width - 4, 6, $black);
        ImageCopy($imgFin, $imgExp, ($width - $widthExp) / 2, $heightSup, 0, 0, $widthExp, $heightExp);
        $this->image = $imgFin;
        $this->verticalBased = $baseExp + $heightSup;
    }

    /**
     * @param $size
     */
    public function drawOverLine($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        //expression
        $this->nodes[1]->draw($size);
        $imgExp = $this->nodes[1]->image;
        $baseExp = $this->nodes[1]->verticalBased;
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);

        $height = $heightExp + 2;
        $width = $widthExp;
        $imgFin = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $width - 1, $height - 1, $white);
        imagesetthickness($imgFin, 1);
        imageline($imgFin, 0, 1, $width, 1, $black);
        ImageCopy($imgFin, $imgExp, 0, 2, 0, 0, $widthExp, $heightExp);
        $this->image = $imgFin;
        $this->verticalBased = $baseExp + 2;
    }

    /**
     * @param $size
     */
    public function drawUnderline($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        //expression
        $this->nodes[1]->draw($size);
        $imgExp = $this->nodes[1]->image;
        $baseExp = $this->nodes[1]->verticalBased;
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);

        $height = $heightExp + 2;
        $width = $widthExp;
        $imgFin = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $width - 1, $height - 1, $white);
        imagesetthickness($imgFin, 1);
        imageline($imgFin, 0, $heightExp + 1, $width, $heightExp + 1, $black);
        ImageCopy($imgFin, $imgExp, 0, 0, 0, 0, $widthExp, $heightExp);
        $this->image = $imgFin;
        $this->verticalBased = $baseExp;
    }

    /**
     * @param $size
     */
    public function drawHat($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $imgSup = $this->helper->displaySymbol("_hat", $size);

        $this->nodes[1]->draw($size);
        $imgExp = $this->nodes[1]->image;
        $baseExp = $this->nodes[1]->verticalBased;
        //expression
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);
        //bornesup
        $widthSup = imagesx($imgSup);
        $heightSup = imagesy($imgSup);
        //fin
        $height = $heightExp + $heightSup;
        $width = max($widthSup, $widthExp) + ceil($size / 8);
        $imgFin = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $width - 1, $height - 1, $white);
        ImageCopy($imgFin, $imgSup, ($width - $widthSup) / 2, 0, 0, 0, $widthSup, $heightSup);
        ImageCopy($imgFin, $imgExp, ($width - $widthExp) / 2, $heightSup, 0, 0, $widthExp, $heightExp);
        $this->image = $imgFin;
        $this->verticalBased = $baseExp + $heightSup;
    }

    /**
     * @param $size
     */
    public function drawLimit($size)
    {
        $backR = $this->helper->getBackR();
        $backG = $this->helper->getBackG();
        $backB = $this->helper->getBackB();
        $fontR = $this->helper->getFontR();
        $fontG = $this->helper->getFontG();
        $fontB = $this->helper->getFontB();
        $transparent = $this->helper->getTransparent();
        $imgLim = $this->helper->affiche_math("_lim", $size);
        $widthLim = imagesx($imgLim);
        $heightLim = imagesy($imgLim);
        $baseLim = $heightLim / 2;

        $this->nodes[1]->draw($size * 0.8);
        $imgInf = $this->nodes[1]->image;
        $baseInf = $this->nodes[1]->verticalBased;
        $widthInf = imagesx($imgInf);
        $heightInf = imagesy($imgInf);

        $this->nodes[2]->draw($size);
        $imgExp = $this->nodes[2]->image;
        $baseExp = $this->nodes[2]->verticalBased;
        $widthExp = imagesx($imgExp);
        $heightExp = imagesy($imgExp);

        $height = $heightLim + $heightInf;
        $width = max($widthInf, $widthLim) + ceil($size / 8);
        $imgFin = ImageCreate(max($width, 1), max($height, 1));
        $black = ImageColorAllocate($imgFin, $fontR, $fontG, $fontB);
        $white = ImageColorAllocate($imgFin, $backR, $backG, $backB);
        if ($transparent) {
            $white = imagecolortransparent($imgFin, $white);
        }
        ImageFilledRectangle($imgFin, 0, 0, $width - 1, $height - 1, $white);
        ImageCopy($imgFin, $imgLim, ($width - $widthLim) / 2, 0, 0, 0, $widthLim, $heightLim);
        ImageCopy($imgFin, $imgInf, ($width - $widthInf) / 2, $heightLim, 0, 0, $widthInf, $heightInf);

        $this->image = $this->helper->alinement2($imgFin, $baseLim, $imgExp, $baseExp);
        $this->verticalBased = max($baseLim, $baseExp);
    }

    /**
     * @param $size
     */
    public function drawDelimiter($size)
    {
        $this->nodes[2]->draw($size);
        $imgExp = $this->nodes[2]->image;
        $baseExp = $this->nodes[2]->verticalBased;
        $heightExp = imagesy($imgExp);
        if ($this->nodes[1]->text == "&$") {
            $leftImg = $this->helper->parenthesis($heightExp, $this->nodes[1]->nodes[0]->text);
        } else {
            $leftImg = $this->helper->parenthesis($heightExp, $this->nodes[1]->text);
        }
        $leftBase = imagesy($leftImg) / 2;
        if ($this->nodes[3]->text == "&$") {
            $rightImg = $this->helper->parenthesis($heightExp, $this->nodes[3]->nodes[0]->text);
        } else {
            $rightImg = $this->helper->parenthesis($heightExp, $this->nodes[3]->text);
        }
        $rightBase = imagesy($rightImg) / 2;
        $this->image = $this->helper->alinement3($leftImg, $leftBase, $imgExp, $baseExp, $rightImg, $rightBase);
        $this->verticalBased = max($leftBase, $baseExp, $rightBase);
    }
}
