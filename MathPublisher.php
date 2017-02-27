<?php

namespace integready\simplemathcaptcha;

/* * *************************************************************************
 *   copyright            : (C) 2005 by Pascal Brachet - France            *
 *   pbrachet_NOSPAM_xm1math.net (replace _NOSPAM_ by @)                   *
 *   http://www.xm1math.net/phpmathpublisher/                              *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 * ************************************************************************* */

/* * ******* HOW TO USE PHPMATHPUBLISHER ****************************
  1) Fix the path to the fonts and the images directory (see PARAMETERS TO MODIFY below)
  2) Include this script in your php page :
  include("MathPublisher.php") ;
  3) Just call the mathfilter($text,$size,$pathtoimg) function in your php page.
  $text is the text with standard html tags and mathematical expressions (defined by the <m>...</m> tag).
  $size is the size of the police used for the formulas.
  $pathtoimg is the relative path between the html pages and the images directory.
  With a simple "echo mathfilter($text,$size,$pathtoimg);", you can display text with mathematical formulas.
  The mathfilter function will replace all the math tags (<m>formula</m>) in $text by <img src=the formula image >.
  Example :
  mathfilter("A math formula : <m>f(x)=sqrt{x}</m>,12,"img/") will return :
  "A math formula : <img src=\"img/math_988.5_903b2b36fc716cfb87ff76a65911a6f0.png\" style=\"vertical-align:-11.5px; display: inline-block ;\" alt=\"f(x)=sqrt{x}\" title=\"f(x)=sqrt{x}\">"
  The image corresponding to a formula is created only once. Then the image is stocked into the image directories.
  The first time that mathfilter is called, the images corresponding to the formulas are created, but the next times mathfilter will only return the html code.

  NOTE : if the free latex fonts furnished with this script don't work well (very tiny formulas - that's could happened with some GD configurations), you should try to use the bakoma versions of these fonts (downloadable here : http://www.ctan.org/tex-archive/fonts/cm/ps-type1/bakoma/ttf/ )
 * ***************************************************************** */

/**
 * Class MathPublisher
 * @package integready\simplemathcaptcha
 */
class MathPublisher
{
    /**
     * @param $texte
     * @param $taille
     *
     * @return resource
     */
    public static function afficheTexte($texte, $taille)
    {
        $dirfonts = GlobalVar::$dirfonts;
        $taille   = max($taille, 6);
        $texte    = stripslashes($texte);
        $font     = $dirfonts . '/cmr10.ttf';
        $htexte   = 'dg' . $texte;
        $hdim     = imagettfbbox($taille, 0, $font, $htexte);
        $wdim     = imagettfbbox($taille, 0, $font, $texte);
        $dx       = max($wdim[2], $wdim[4]) - min($wdim[0], $wdim[6]) + ceil($taille / 8);
        $dy       = max($hdim[1], $hdim[3]) - min($hdim[5], $hdim[7]) + ceil($taille / 8);
        $img      = imagecreate(max($dx, 1), max($dy, 1));
        $noir     = imagecolorallocate($img, 0, 0, 0);
        $blanc    = imagecolorallocate($img, 255, 255, 255);
        $blanc    = imagecolortransparent($img, $blanc);
        imagefilledrectangle($img, 0, 0, $dx, $dy, $blanc);
//ImageRectangle($img,0,0,$dx-1,$dy-1,$noir);
        imagettftext($img, $taille, $angle, 0, -min($hdim[5], $hdim[7]), $noir, $font, $texte);

        return $img;
    }

    /**
     * @param $texte
     * @param $taille
     *
     * @return resource
     */
    public static function afficheMath($texte, $taille)
    {
        $dirfonts = GlobalVar::$dirfonts;
        $taille   = max($taille, 6);

        $symboles   = GlobalVar::$symboles;
        $fontesmath = GlobalVar::$fontesmath;
        $texte      = stripslashes($texte);
        if (isset($fontesmath[$texte])) {
            $font = $dirfonts . '/' . $fontesmath[$texte] . '.ttf';
        } elseif (preg_match('/[a-zA-Z]/', $texte)) {
            $font = $dirfonts . '/cmmi10.ttf';
        } else {
            $font = $dirfonts . '/cmr10.ttf';
        }
        if (isset($symboles[$texte])) {
            $texte = $symboles[$texte];
        }
        $htexte = 'dg' . $texte;
        $hdim   = imagettfbbox($taille, 0, $font, $htexte);
        $wdim   = imagettfbbox($taille, 0, $font, $texte);
        $dx     = max($wdim[2], $wdim[4]) - min($wdim[0], $wdim[6]) + ceil($taille / 8);
        $dy     = max($hdim[1], $hdim[3]) - min($hdim[5], $hdim[7]) + ceil($taille / 8);
        $img    = imagecreate(max($dx, 1), max($dy, 1));
        $noir   = imagecolorallocate($img, 0, 0, 0);
        $blanc  = imagecolorallocate($img, 255, 255, 255);
        $blanc  = imagecolortransparent($img, $blanc);
        imagefilledrectangle($img, 0, 0, $dx, $dy, $blanc);
//ImageRectangle($img,0,0,$dx-1,$dy-1,$noir);
        imagettftext($img, $taille, 0, 0, -min($hdim[5], $hdim[7]), $noir, $font, $texte);

        return $img;
    }

// ugly hack, but GD is not very good with truetype fonts (especially with latex fonts)

    /**
     * @param $hauteur
     * @param $style
     *
     * @return resource
     */
    public static function parenthese($hauteur, $style)
    {
        $image = MathPublisher::afficheSymbol($style, $hauteur);

        return $image;
    }

    /**
     * @param $texte
     * @param $haut
     *
     * @return resource
     */
    public static function afficheSymbol($texte, $haut)
    {
        $dirfonts   = GlobalVar::$dirfonts;
        $symboles   = GlobalVar::$symboles;
        $fontesmath = GlobalVar::$fontesmath;
        $texte      = trim(stripslashes($texte));
        switch ($texte) {
            case '':
                $img   = imagecreate(1, max($haut, 1));
                $blanc = imagecolorallocate($img, 255, 255, 255);
                $blanc = imagecolortransparent($img, $blanc);
                imagefilledrectangle($img, 0, 0, 1, $haut, $blanc);
                break;
            case '~':
                $img   = imagecreate(1, max($haut, 1));
                $blanc = imagecolorallocate($img, 255, 255, 255);
                $blanc = imagecolortransparent($img, $blanc);
                imagefilledrectangle($img, 0, 0, 1, $haut, $blanc);
                break;
            case 'vert':
                $img   = imagecreate(6, max($haut, 1));
                $blanc = imagecolorallocate($img, 255, 255, 255);
                $blanc = imagecolortransparent($img, $blanc);
                $noir  = imagecolorallocate($img, 0, 0, 0);
                imagefilledrectangle($img, 0, 0, 6, $haut, $blanc);
                imagefilledrectangle($img, 2, 0, 2, $haut, $noir);
                imagefilledrectangle($img, 4, 0, 4, $haut, $noir);
                break;
            case '|':
                $img   = imagecreate(5, max($haut, 1));
                $blanc = imagecolorallocate($img, 255, 255, 255);
                $blanc = imagecolortransparent($img, $blanc);
                $noir  = imagecolorallocate($img, 0, 0, 0);
                imagefilledrectangle($img, 0, 0, 5, $haut, $blanc);
                imagefilledrectangle($img, 2, 0, 2, $haut, $noir);
                break;
            case 'right':
                $font        = $dirfonts . '/' . $fontesmath[$texte] . '.ttf';
                $t           = 16;
                $texte       = $symboles[$texte];
                $tmp_dim     = imagettfbbox($t, 0, $font, $texte);
                $tmp_largeur = abs($tmp_dim[2] - $tmp_dim[0]) + 2;
                $tmp_hauteur = abs($tmp_dim[3] - $tmp_dim[5]) + 2;
                $tmp_img     = imagecreate(max($tmp_largeur, 1), max($tmp_hauteur, 1));
                $tmp_noir    = imagecolorallocate($tmp_img, 0, 0, 0);
                $tmp_blanc   = imagecolorallocate($tmp_img, 255, 255, 255);
                $tmp_blanc   = imagecolortransparent($tmp_img, $tmp_blanc);
                imagefilledrectangle($tmp_img, 0, 0, $tmp_largeur, $tmp_hauteur, $tmp_blanc);
                imagettftext($tmp_img, $t, 0, 0, $tmp_hauteur, $tmp_noir, $font, $texte);
                $sx = $sy = $ex = $ey = -1;
                for ($y = 0; $y < $tmp_hauteur; $y++) {
                    for ($x = 0; $x < $tmp_largeur; $x++) {
                        $rgb = imagecolorat($tmp_img, $x, $y);
                        if ($rgb != $tmp_blanc) {
                            if ($sy == -1) {
                                $sy = $y;
                            } else {
                                $ey = $y;
                            }

                            if ($sx == -1) {
                                $sx = $x;
                            } else {
                                if ($x < $sx) {
                                    $sx = $x;
                                } elseif ($x > $ex) {
                                    $ex = $x;
                                }
                            }
                        }
                    }
                }
                $nx    = abs($ex - $sx);
                $ny    = abs($ey - $sy);
                $img   = imagecreate(max($nx + 4, 1), max($ny + 4, 1));
                $blanc = imagecolorallocate($img, 255, 255, 255);
                $blanc = imagecolortransparent($img, $blanc);
                imagefilledrectangle($img, 0, 0, $nx + 4, $ny + 4, $blanc);
                imagecopy($img, $tmp_img, 2, 2, $sx, $sy, min($nx + 2, $tmp_largeur - $sx), min($ny + 2, $tmp_hauteur - $sy));
                break;
            case '_hat':
                $font        = $dirfonts . '/' . $fontesmath[$texte] . '.ttf';
                $t           = $haut;
                $texte       = $symboles[$texte];
                $tmp_dim     = imagettfbbox($t, 0, $font, $texte);
                $tmp_largeur = abs($tmp_dim[2] - $tmp_dim[0]);
                $tmp_hauteur = abs($tmp_dim[3] - $tmp_dim[5]) * 4;
                $tmp_img     = imagecreate(max($tmp_largeur, 1), max($tmp_hauteur, 1));
                $tmp_noir    = imagecolorallocate($tmp_img, 0, 0, 0);
                $tmp_blanc   = imagecolorallocate($tmp_img, 255, 255, 255);
                $tmp_blanc   = imagecolortransparent($tmp_img, $tmp_blanc);
                imagefilledrectangle($tmp_img, 0, 0, $tmp_largeur, $tmp_hauteur, $tmp_blanc);
                imagettftext($tmp_img, $t, 0, 0, $tmp_hauteur, $tmp_noir, $font, $texte);
                $sx = $sy = $ex = $ey = -1;
                for ($y = 0; $y < $tmp_hauteur; $y++) {
                    for ($x = 0; $x < $tmp_largeur; $x++) {
                        $rgb = imagecolorat($tmp_img, $x, $y);
                        if ($rgb != $tmp_blanc) {
                            if ($sy == -1) {
                                $sy = $y;
                            } else {
                                $ey = $y;
                            }

                            if ($sx == -1) {
                                $sx = $x;
                            } else {
                                if ($x < $sx) {
                                    $sx = $x;
                                } elseif ($x > $ex) {
                                    $ex = $x;
                                }
                            }
                        }
                    }
                }
                $nx    = abs($ex - $sx);
                $ny    = abs($ey - $sy);
                $img   = imagecreate(max($nx + 4, 1), max($ny + 4, 1));
                $blanc = imagecolorallocate($img, 255, 255, 255);
                $blanc = imagecolortransparent($img, $blanc);
                imagefilledrectangle($img, 0, 0, $nx + 4, $ny + 4, $blanc);
                imagecopy($img, $tmp_img, 2, 2, $sx, $sy, min($nx + 2, $tmp_largeur - $sx), min($ny + 2, $tmp_hauteur - $sy));
                break;
            case '_dintegrale':
            case '_tintegrale':
                if (isset($fontesmath[$texte])) {
                    $font = $dirfonts . '/' . $fontesmath[$texte] . '.ttf';
                } elseif (self::estNombre($texte)) {
                    $font = $dirfonts . '/cmr10.ttf';
                } else {
                    $font = $dirfonts . '/cmmi10.ttf';
                }
                $t = 6;
                if (isset($symboles[$texte])) {
                    $texte = $symboles[$texte];
                }
                do {
                    $tmp_dim = imagettfbbox($t, 0, $font, $texte);
                    $t       += 1;
                } while ((abs($tmp_dim[3] - $tmp_dim[5]) < 1.2 * $haut));
                $tmp_largeur = abs($tmp_dim[2] - $tmp_dim[0]) * 2;
                $tmp_hauteur = abs($tmp_dim[3] - $tmp_dim[5]) * 2;
                $tmp_img     = imagecreate(max($tmp_largeur, 1), max($tmp_hauteur, 1));
                $tmp_noir    = imagecolorallocate($tmp_img, 0, 0, 0);
                $tmp_blanc   = imagecolorallocate($tmp_img, 255, 255, 255);
                $tmp_blanc   = imagecolortransparent($tmp_img, $tmp_blanc);
                imagefilledrectangle($tmp_img, 0, 0, $tmp_largeur, $tmp_hauteur, $tmp_blanc);
                imagettftext($tmp_img, $t, 0, 5, $tmp_hauteur / 2, $tmp_noir, $font, $texte);
                $toutblanc = true;
                $sx        = $sy = $ex = $ey = -1;
                for ($y = 0; $y < $tmp_hauteur; $y++) {
                    for ($x = 0; $x < $tmp_largeur; $x++) {
                        $rgb = imagecolorat($tmp_img, $x, $y);
                        if ($rgb != $tmp_blanc) {
                            $toutblanc = false;
                            if ($sy == -1) {
                                $sy = $y;
                            } else {
                                $ey = $y;
                            }

                            if ($sx == -1) {
                                $sx = $x;
                            } else {
                                if ($x < $sx) {
                                    $sx = $x;
                                } elseif ($x > $ex) {
                                    $ex = $x;
                                }
                            }
                        }
                    }
                }
                $nx = abs($ex - $sx);
                $ny = abs($ey - $sy);
                if ($toutblanc) {
                    $img   = imagecreate(1, max($haut, 1));
                    $blanc = imagecolorallocate($img, 255, 255, 255);
                    $blanc = imagecolortransparent($img, $blanc);
                    imagefilledrectangle($img, 0, 0, 1, $haut, $blanc);
                } else {
                    $img   = imagecreate(max($nx + 4, 1), max($ny + 4, 1));
                    $blanc = imagecolorallocate($img, 255, 255, 255);
                    $blanc = imagecolortransparent($img, $blanc);
                    imagefilledrectangle($img, 0, 0, $nx + 4, $ny + 4, $blanc);
                    imagecopy($img, $tmp_img, 2, 2, $sx, $sy, min($nx + 2, $tmp_largeur - $sx), min($ny + 2, $tmp_hauteur - $sy));
                }
                break;
            default:
                if (isset($fontesmath[$texte])) {
                    $font = $dirfonts . '/' . $fontesmath[$texte] . '.ttf';
                } elseif (self::estNombre($texte)) {
                    $font = $dirfonts . '/cmr10.ttf';
                } else {
                    $font = $dirfonts . '/cmmi10.ttf';
                }
                $t = 6;
                if (isset($symboles[$texte])) {
                    $texte = $symboles[$texte];
                }
                do {
                    $tmp_dim = imagettfbbox($t, 0, $font, $texte);
                    $t       += 1;
                } while ((abs($tmp_dim[3] - $tmp_dim[5]) < $haut));
                $tmp_largeur = abs($tmp_dim[2] - $tmp_dim[0]) * 2;
                $tmp_hauteur = abs($tmp_dim[3] - $tmp_dim[5]) * 2;
                $tmp_img     = imagecreate(max($tmp_largeur, 1), max($tmp_hauteur, 1));
                $tmp_noir    = imagecolorallocate($tmp_img, 0, 0, 0);
                $tmp_blanc   = imagecolorallocate($tmp_img, 255, 255, 255);
                $tmp_blanc   = imagecolortransparent($tmp_img, $tmp_blanc);
                imagefilledrectangle($tmp_img, 0, 0, $tmp_largeur, $tmp_hauteur, $tmp_blanc);
                imagettftext($tmp_img, $t, 0, 0, $tmp_hauteur / 4, $tmp_noir, $font, $texte);
// 	ImageTTFText($tmp_img, $t, 0,5,5,$tmp_noir, $font,$texte);
//	$img=$tmp_img;
                $toutblanc = true;
                $sx        = $sy = $ex = $ey = -1;
                for ($y = 0; $y < $tmp_hauteur; $y++) {
                    for ($x = 0; $x < $tmp_largeur; $x++) {
                        $rgb = imagecolorat($tmp_img, $x, $y);
                        if ($rgb != $tmp_blanc) {
                            $toutblanc = false;
                            if ($sy == -1) {
                                $sy = $y;
                            } else {
                                $ey = $y;
                            }

                            if ($sx == -1) {
                                $sx = $x;
                            } else {
                                if ($x < $sx) {
                                    $sx = $x;
                                } elseif ($x > $ex) {
                                    $ex = $x;
                                }
                            }
                        }
                    }
                }
                $nx = abs($ex - $sx);
                $ny = abs($ey - $sy);
                if ($toutblanc) {
                    $img   = imagecreate(1, max($haut, 1));
                    $blanc = imagecolorallocate($img, 255, 255, 255);
                    $blanc = imagecolortransparent($img, $blanc);
                    imagefilledrectangle($img, 0, 0, 1, $haut, $blanc);
                } else {
                    $img   = imagecreate(max($nx + 4, 1), max($ny + 4, 1));
                    $blanc = imagecolorallocate($img, 255, 255, 255);
                    $blanc = imagecolortransparent($img, $blanc);
                    imagefilledrectangle($img, 0, 0, $nx + 4, $ny + 4, $blanc);
                    imagecopy($img, $tmp_img, 2, 2, $sx, $sy, min($nx + 2, $tmp_largeur - $sx), min($ny + 2, $tmp_hauteur - $sy));
                }
                break;
        }
//$rouge=ImageColorAllocate($img,255,0,0);
//ImageRectangle($img,0,0,ImageSX($img)-1,ImageSY($img)-1,$rouge);
        return $img;
    }

    /**
     * @param $str
     *
     * @return int
     */
    private static function estNombre($str)
    {
        return preg_match('/^[0-9]/', $str);
    }

    /**
     * @param $image1
     * @param $base1
     * @param $image2
     * @param $base2
     *
     * @return resource
     */
    public static function alignement2($image1, $base1, $image2, $base2)
    {
        $largeur1 = imagesx($image1);
        $hauteur1 = imagesy($image1);
        $largeur2 = imagesx($image2);
        $hauteur2 = imagesy($image2);
        $dessus   = max($base1, $base2);
        $dessous  = max($hauteur1 - $base1, $hauteur2 - $base2);
        $largeur  = $largeur1 + $largeur2;
        $hauteur  = $dessus + $dessous;
        $result   = imagecreate(max($largeur, 1), max($hauteur, 1));
        $blanc    = imagecolorallocate($result, 255, 255, 255);
        $blanc    = imagecolortransparent($result, $blanc);
        imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($result, $image1, 0, $dessus - $base1, 0, 0, $largeur1, $hauteur1);
        imagecopy($result, $image2, $largeur1, $dessus - $base2, 0, 0, $largeur2, $hauteur2);

//ImageRectangle($result,0,0,$largeur-1,$hauteur-1,$noir);
        return $result;
    }

    /**
     * @param $image1
     * @param $base1
     * @param $image2
     * @param $base2
     * @param $image3
     * @param $base3
     *
     * @return resource
     */
    public static function alignement3($image1, $base1, $image2, $base2, $image3, $base3)
    {
        $largeur1 = imagesx($image1);
        $hauteur1 = imagesy($image1);
        $largeur2 = imagesx($image2);
        $hauteur2 = imagesy($image2);
        $largeur3 = imagesx($image3);
        $hauteur3 = imagesy($image3);
        $dessus   = max($base1, $base2, $base3);
        $dessous  = max($hauteur1 - $base1, $hauteur2 - $base2, $hauteur3 - $base3);
        $largeur  = $largeur1 + $largeur2 + $largeur3;
        $hauteur  = $dessus + $dessous;
        $result   = imagecreate(max($largeur, 1), max($hauteur, 1));
        $blanc    = imagecolorallocate($result, 255, 255, 255);
        $blanc    = imagecolortransparent($result, $blanc);
        imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($result, $image1, 0, $dessus - $base1, 0, 0, $largeur1, $hauteur1);
        imagecopy($result, $image2, $largeur1, $dessus - $base2, 0, 0, $largeur2, $hauteur2);
        imagecopy($result, $image3, $largeur1 + $largeur2, $dessus - $base3, 0, 0, $largeur3, $hauteur3);

//ImageRectangle($result,0,0,$largeur-1,$hauteur-1,$noir);
        return $result;
    }

    /**
     * @param $text
     * @param $size
     * @param $pathtoimg
     *
     * @return mixed|string
     */
    private static function mathFilter($text, $size, $pathtoimg)
    {
        /* THE MAIN FUNCTION
          1) the content of the math tags (<m></m>) are extracted in the $t variable (you can replace <m></m> by your own tag).
          2) the "mathimage" function replaces the $t code by <img src=...></img> according to this method :
          - if the image corresponding to the formula doesn't exist in the $dirimg cache directory (detectimg($nameimg)=0), the script creates the image and returns the "<img src=...></img>" code.
          - otherwise, the script returns only the <img src=...></img>" code.
          To align correctly the formula image with the text, the "valign" parameter of the image is required.
          That's why a parameter (1000+valign) is recorded in the name of the image file (the "detectimg" function returns this parameter if the image exists in the cache directory)
          To be sure that the name of the image file is unique and to allow the script to retrieve the valign parameter without re-creating the image, the syntax of the image filename is :
          math_(1000+valign)_md5(formulatext.size).png.
          (1000+valign is used instead of valign directly to avoid a negative number)
         */
        $text = stripslashes($text);
        $size = max($size, 10);
        $size = min($size, 24);
        preg_match_all('|<m>(.*?)</m>|', $text, $regs, PREG_SET_ORDER);
        foreach ($regs as $math) {
            $t    = str_replace('<m>', '', $math[0]);
            $t    = str_replace('</m>', '', $t);
            $code = self::mathImage(trim($t), $size, $pathtoimg);
            $text = str_replace($math[0], $code, $text);
        }

        return $text;
    }

    /**
     * @param $text
     * @param $size
     * @param $pathtoimg
     *
     * @return string
     */
    private static function mathImage($text, $size, $pathtoimg)
    {
        /*
          Creates the formula image (if the image is not in the cache) and returns the <img src=...></img> html code.
         */
        $dirimg  = GlobalVar::$dirimg;
        $nameimg = md5(trim($text) . $size) . '.png';
        $v       = self::detectimg($nameimg);
        if ($v == 0) {
            //the image doesn't exist in the cache directory. we create it.
            $formula = new ExpressionMath(MathPublisher::tableauExpression(trim($text)));
            $formula->dessine($size);
            $v = 1000 - imagesy($formula->image) + $formula->base_verticale + 3;
            //1000+baseline ($v) is recorded in the name of the image
            imagepng($formula->image, $dirimg . '/math_' . $v . '_' . $nameimg);
        }
        $valign = $v - 1000;

        return '<img src="' . $pathtoimg . 'math_' . $v . '_' . $nameimg . '" style="vertical-align:' . $valign . 'px;' . ' display: inline-block ;" alt="' . $text . '" title="' . $text . '"/>';
    }

    /**
     * @param $n
     *
     * @return int
     */
    private static function detectimg($n)
    {
        /*
          Detects if the formula image already exists in the $dirimg cache directory.
          In that case, the function returns a parameter (recorded in the name of the image file) which allows to align correctly the image with the text.
         */
        $dirimg = GlobalVar::$dirimg;
        $ret    = 0;
        $handle = opendir($dirimg);
        while ($fi = readdir($handle)) {
            $info = pathinfo($fi);
            if ($fi != '.' && $fi != '..' && $info['extension'] == 'png' && preg_match('/^math/', $fi)) {
                list($math, $v, $name) = explode('_', $fi);
                if ($math && $name == $n) {
                    $ret = $v;
                    break;
                }
            }
        }
        closedir($handle);

        return $ret;
    }

    /**
     * @param $expression
     *
     * @return array
     */
    public static function tableauExpression($expression)
    {
        $e        = str_replace('_', ' _ ', $expression);
        $e        = str_replace('{(}', '{ }', $e);
        $e        = str_replace('{)}', '{ }', $e);
        $t        = token_get_all("<?php \$formula=$e ?" . '>');
        $extraits = [];
        $result   = [];
//stupid code but token_get_all bug in some php versions
        $d = 0;
        for ($i = 0; $i < count($t); $i++) {
            if (is_array($t[$i])) {
                $t[$i] = $t[$i][1];
            }
            if (preg_match('/formula/', $t[$i])) {
                $d = $i + 2;
                break;
            }
        }
        for ($i = $d; $i < count($t) - 1; $i++) {
            if (is_array($t[$i])) {
                $t[$i] = $t[$i][1];
            }
            if ($t[$i] == '<=') {
                $t[$i] = 'le';
            } elseif ($t[$i] == '!=') {
                $t[$i] = 'ne';
            } elseif ($t[$i] == '<>') {
                $t[$i] = 'ne';
            } elseif ($t[$i] == '>=') {
                $t[$i] = 'ge';
            } elseif ($t[$i] == '--') {
                $t[$i]     = '-';
                $t[$i + 1] = '-' . $t[$i + 1];
            } elseif ($t[$i] == '++') {
                $t[$i] = '+';
            } elseif ($t[$i] == '-') {
                if ($t[$i - 1] == '^' || $t[$i - 1] == '_' || $t[$i - 1] == '*' || $t[$i - 1] == '/' || $t[$i - 1] == '+' || $t[$i - 1] == '(') {
                    $t[$i] = '';
                    if (is_array($t[$i + 1])) {
                        $t[$i + 1][1] = '-' . $t[$i + 1][1];
                    } else {
                        $t[$i + 1] = '-' . $t[$i + 1];
                    }
                }
            }
            if (trim($t[$i]) != '') {
                $extraits[] = $t[$i];
            }
        }
        for ($i = 0; $i < count($extraits); $i++) {
            $result[] = new ExpressionTexte($extraits[$i]);
        }

        return $result;
    }
}
