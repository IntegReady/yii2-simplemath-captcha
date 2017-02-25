<?php

namespace integready\simplemathcaptcha;

/**
 * Class ExpressionMath
 * @package integready\simplemathcaptcha
 *
 * @property ExpressionMath[] $noeuds
 */
class ExpressionMath extends Expression
{
    public $noeuds;

    /**
     * @param $exp
     */
    public function expressionMath($exp)
    {
        $this->texte  = '&$';
        $this->noeuds = $exp;
        $this->noeuds = $this->parse();
    }

    /**
     * @return array
     */
    public function parse()
    {
        if (count($this->noeuds) <= 3) {
            return $this->noeuds;
        }
        $ret         = [];
        $parentheses = [];
        for ($i = 0; $i < count($this->noeuds); $i++) {
            if ($this->noeuds[$i]->texte == '(' || $this->noeuds[$i]->texte == '{') {
                array_push($parentheses, $i);
            } elseif ($this->noeuds[$i]->texte == ')' || $this->noeuds[$i]->texte == '}') {
                $pos = array_pop($parentheses);
                if (count($parentheses) == 0) {
                    $sub = array_slice($this->noeuds, $pos + 1, $i - $pos - 1);
                    if ($this->noeuds[$i]->texte == ')') {
                        $ret[] = new ExpressionMath([new ExpressionTexte('('), new ExpressionMath($sub), new ExpressionTexte(')')]);
                    } else {
                        $ret[] = new ExpressionMath($sub);
                    }
                }
            } elseif (count($parentheses) == 0) {
                $ret[] = $this->noeuds[$i];
            }
        }
        $ret = $this->traiteFonction($ret, 'sqrt', 1);
        $ret = $this->traiteFonction($ret, 'vec', 1);
        $ret = $this->traiteFonction($ret, 'overline', 1);
        $ret = $this->traiteFonction($ret, 'underline', 1);
        $ret = $this->traiteFonction($ret, 'hat', 1);
        $ret = $this->traiteFonction($ret, 'int', 3);
        $ret = $this->traiteFonction($ret, 'doubleint', 3);
        $ret = $this->traiteFonction($ret, 'tripleint', 3);
        $ret = $this->traiteFonction($ret, 'oint', 3);
        $ret = $this->traiteFonction($ret, 'prod', 3);
        $ret = $this->traiteFonction($ret, 'sum', 3);
        $ret = $this->traiteFonction($ret, 'bigcup', 3);
        $ret = $this->traiteFonction($ret, 'bigcap', 3);
        $ret = $this->traiteFonction($ret, 'delim', 3);
        $ret = $this->traiteFonction($ret, 'lim', 2);
        $ret = $this->traiteFonction($ret, 'root', 2);
        $ret = $this->traiteFonction($ret, 'matrix', 3);
        $ret = $this->traiteFonction($ret, 'tabular', 3);

        $ret = $this->traiteOperation($ret, '^');
        $ret = $this->traiteOperation($ret, 'over');
        $ret = $this->traiteOperation($ret, '_');
        $ret = $this->traiteOperation($ret, 'under');
        $ret = $this->traiteOperation($ret, '*');
        $ret = $this->traiteOperation($ret, '/');
        $ret = $this->traiteOperation($ret, '+');
        $ret = $this->traiteOperation($ret, '-');

        return $ret;
    }

    /**
     * @param $noeuds
     * @param $fonction
     * @param $nbarg
     *
     * @return array
     */
    public function traiteFonction($noeuds, $fonction, $nbarg)
    {
        if (count($noeuds) <= $nbarg + 1) {
            return $noeuds;
        }
        $ret = [];
        for ($i = 0; $i < count($noeuds); $i++) {
            if ($i < count($noeuds) - $nbarg && $noeuds[$i]->texte == $fonction) {
                $a = [];
                for ($j = $i; $j <= $i + $nbarg; $j++) {
                    $a[] = $noeuds[$j];
                }
                $ret[] = new ExpressionMath($a);
                $i     += $nbarg;
            } else {
                $ret[] = $noeuds[$i];
            }
        }

        return $ret;
    }

    /**
     * @param $noeuds
     * @param $operation
     *
     * @return array
     */
    public function traiteOperation($noeuds, $operation)
    {
        do {
            $change = false;
            if (count($noeuds) <= 3) {
                return $noeuds;
            }
            $ret = [];
            for ($i = 0; $i < count($noeuds); $i++) {
                if (!$change && $i < count($noeuds) - 2 && $noeuds[$i + 1]->texte == $operation) {
                    $ret[]  = new ExpressionMath([$noeuds[$i], $noeuds[$i + 1], $noeuds[$i + 2]]);
                    $i      += 2;
                    $change = true;
                } else {
                    $ret[] = $noeuds[$i];
                }
            }
            $noeuds = $ret;
        } while ($change);

        return $ret;
    }

    /**
     * @param $taille
     */
    public function dessine($taille)
    {
        switch (count($this->noeuds)) {
            case 1:
                $this->noeuds[0]->dessine($taille);
                $this->image          = $this->noeuds[0]->image;
                $this->base_verticale = $this->noeuds[0]->base_verticale;
                break;
            case 2:
                switch ($this->noeuds[0]->texte) {
                    case 'sqrt':
                        $this->dessineRacine($taille);
                        break;
                    case 'vec':
                        $this->dessineVecteur($taille);
                        break;
                    case 'overline':
                        $this->dessineOverline($taille);
                        break;
                    case 'underline':
                        $this->dessineUnderline($taille);
                        break;
                    case 'hat':
                        $this->dessineChapeau($taille);
                        break;
                    default:
                        $this->dessineExpression($taille);
                        break;
                }
                break;
            case 3:
                if ($this->noeuds[0]->texte == 'lim') {
                    $this->dessineLimite($taille);
                } elseif ($this->noeuds[0]->texte == 'root') {
                    $this->dessineRoot($taille);
                } else {
                    switch ($this->noeuds[1]->texte) {
                        case '/':
                            $this->dessineFraction($taille);
                            break;
                        case '^':
                            $this->dessineExposant($taille);
                            break;
                        case 'over':
                            $this->dessineDessus($taille);
                            break;
                        case '_':
                            $this->dessineIndice($taille);
                            break;
                        case 'under':
                            $this->dessineDessous($taille);
                            break;
                        default:
                            $this->dessineExpression($taille);
                            break;
                    }
                }
                break;
            case 4:
                switch ($this->noeuds[0]->texte) {
                    case 'int':
                        $this->dessineGrandoperateur($taille, '_integrale');
                        break;
                    case 'doubleint':
                        $this->dessineGrandoperateur($taille, '_dintegrale');
                        break;
                    case 'tripleint':
                        $this->dessineGrandoperateur($taille, '_tintegrale');
                        break;
                    case 'oint':
                        $this->dessineGrandoperateur($taille, '_ointegrale');
                        break;
                    case 'sum':
                        $this->dessineGrandoperateur($taille, '_somme');
                        break;
                    case 'prod':
                        $this->dessineGrandoperateur($taille, '_produit');
                        break;
                    case 'bigcap':
                        $this->dessineGrandoperateur($taille, '_intersection');
                        break;
                    case 'bigcup':
                        $this->dessineGrandoperateur($taille, '_reunion');
                        break;
                    case 'delim':
                        $this->dessineDelimiteur($taille);
                        break;
                    case 'matrix':
                        $this->dessineMatrice($taille);
                        break;
                    case 'tabular':
                        $this->dessineTableau($taille);
                        break;
                    default:
                        $this->dessineExpression($taille);
                        break;
                }
                break;
            default:
                $this->dessineExpression($taille);
                break;
        }
    }

    /**
     * @param $taille
     */
    private function dessineRacine($taille)
    {
        $this->noeuds[1]->dessine($taille);
        $imgexp     = $this->noeuds[1]->image;
        $baseexp    = $this->noeuds[1]->base_verticale;
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);

        $imgrac     = MathPublisher::afficheSymbol('_racine', $hauteurexp + 2);
        $largeurrac = imagesx($imgrac);
        $hauteurrac = imagesy($imgrac);
        $baserac    = $hauteurrac / 2;

        $largeur = $largeurrac + $largeurexp;
        $hauteur = max($hauteurexp, $hauteurrac);
        $result  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($result, 0, 0, 0);
        $blanc   = imagecolorallocate($result, 255, 255, 255);
        $blanc   = imagecolortransparent($result, $blanc);
        imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($result, $imgrac, 0, 0, 0, 0, $largeurrac, $hauteurrac);
        imagecopy($result, $imgexp, $largeurrac, $hauteur - $hauteurexp, 0, 0, $largeurexp, $hauteurexp);
        imagesetthickness($result, 1);
        imageline($result, $largeurrac - 2, 2, $largeurrac + $largeurexp + 2, 2, $noir);
        $this->base_verticale = $hauteur - $hauteurexp + $baseexp;
        $this->image          = $result;
    }

    /**
     * @param $taille
     */
    private function dessineVecteur($taille)
    {
//expression
        $this->noeuds[1]->dessine($taille);
        $imgexp     = $this->noeuds[1]->image;
        $baseexp    = $this->noeuds[1]->base_verticale;
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);
//fleche
        $imgsup     = MathPublisher::afficheSymbol('right', 16);
        $largeursup = imagesx($imgsup);
        $hauteursup = imagesy($imgsup);
//fin
        $hauteur = $hauteurexp + $hauteursup;
        $largeur = $largeurexp;
        $imgfin  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc   = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc   = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($imgfin, $imgsup, $largeur - 6, 0, $largeursup - 6, 0, $largeursup, $hauteursup);
        imagesetthickness($imgfin, 1);
        imageline($imgfin, 0, 6, $largeur - 4, 6, $noir);
        imagecopy($imgfin, $imgexp, ($largeur - $largeurexp) / 2, $hauteursup, 0, 0, $largeurexp, $hauteurexp);
        $this->image          = $imgfin;
        $this->base_verticale = $baseexp + $hauteursup;
    }

    /**
     * @param $taille
     */
    private function dessineOverline($taille)
    {
//expression
        $this->noeuds[1]->dessine($taille);
        $imgexp     = $this->noeuds[1]->image;
        $baseexp    = $this->noeuds[1]->base_verticale;
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);

        $hauteur = $hauteurexp + 2;
        $largeur = $largeurexp;
        $imgfin  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc   = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc   = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagesetthickness($imgfin, 1);
        imageline($imgfin, 0, 1, $largeur, 1, $noir);
        imagecopy($imgfin, $imgexp, 0, 2, 0, 0, $largeurexp, $hauteurexp);
        $this->image          = $imgfin;
        $this->base_verticale = $baseexp + 2;
    }

    /**
     * @param $taille
     */
    private function dessineUnderline($taille)
    {
//expression
        $this->noeuds[1]->dessine($taille);
        $imgexp     = $this->noeuds[1]->image;
        $baseexp    = $this->noeuds[1]->base_verticale;
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);

        $hauteur = $hauteurexp + 2;
        $largeur = $largeurexp;
        $imgfin  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc   = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc   = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagesetthickness($imgfin, 1);
        imageline($imgfin, 0, $hauteurexp + 1, $largeur, $hauteurexp + 1, $noir);
        imagecopy($imgfin, $imgexp, 0, 0, 0, 0, $largeurexp, $hauteurexp);
        $this->image          = $imgfin;
        $this->base_verticale = $baseexp;
    }

    /**
     * @param $taille
     */
    private function dessineChapeau($taille)
    {

        $imgsup = MathPublisher::afficheSymbol('_hat', $taille);

        $this->noeuds[1]->dessine($taille);
        $imgexp  = $this->noeuds[1]->image;
        $baseexp = $this->noeuds[1]->base_verticale;
//expression
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);
//bornesup
        $largeursup = imagesx($imgsup);
        $hauteursup = imagesy($imgsup);
//fin
        $hauteur = $hauteurexp + $hauteursup;
        $largeur = max($largeursup, $largeurexp) + ceil($taille / 8);
        $imgfin  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc   = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc   = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($imgfin, $imgsup, ($largeur - $largeursup) / 2, 0, 0, 0, $largeursup, $hauteursup);
        imagecopy($imgfin, $imgexp, ($largeur - $largeurexp) / 2, $hauteursup, 0, 0, $largeurexp, $hauteurexp);
        $this->image          = $imgfin;
        $this->base_verticale = $baseexp + $hauteursup;
    }

    /**
     * @param $taille
     */
    private function dessineExpression($taille)
    {
        $img     = [];
        $base    = [];
        $largeur = 1;
        $hauteur = 1;
        $dessus  = 1;
        $dessous = 1;
        for ($i = 0; $i < count($this->noeuds); $i++) {
            if ($this->noeuds[$i]->texte != '(' && $this->noeuds[$i]->texte != ')') {
                $this->noeuds[$i]->dessine($taille);
                $img[$i]  = $this->noeuds[$i]->image;
                $base[$i] = $this->noeuds[$i]->base_verticale;
                $dessus   = max($base[$i], $dessus);
                $dessous  = max(imagesy($img[$i]) - $base[$i], $dessous);
            }
        }
        $hauteur = $dessus + $dessous;
        $paro    = MathPublisher::parenthese(max($dessus, $dessous) * 2, '(');
        $parf    = MathPublisher::parenthese(max($dessus, $dessous) * 2, ')');
        for ($i = 0; $i < count($this->noeuds); $i++) {
            if (!isset($img[$i])) {
                if ($this->noeuds[$i]->texte == '(') {
                    $img[$i] = $paro;
                } else {
                    $img[$i] = $parf;
                }
                $dessus   = max(imagesy($img[$i]) / 2, $dessus);
                $base[$i] = imagesy($img[$i]) / 2;
                $dessous  = max(imagesy($img[$i]) - $base[$i], $dessous);
                $hauteur  = max(imagesy($img[$i]), $hauteur);
            }
            $largeur += imagesx($img[$i]);
        }
        $this->base_verticale = $dessus;
        $result               = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir                 = imagecolorallocate($result, 0, 0, 0);
        $blanc                = imagecolorallocate($result, 255, 255, 255);
        $blanc                = imagecolortransparent($result, $blanc);
        imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        $pos = 0;
        for ($i = 0; $i < count($img); $i++) {
            if (isset($img[$i])) {
                imagecopy($result, $img[$i], $pos, $dessus - $base[$i], 0, 0, imagesx($img[$i]), imagesy($img[$i]));
                $pos += imagesx($img[$i]);
            }
        }
        $this->image = $result;
    }

    /**
     * @param $taille
     */
    private function dessineLimite($taille)
    {
        $imglim     = MathPublisher::afficheMath('_lim', $taille);
        $largeurlim = imagesx($imglim);
        $hauteurlim = imagesy($imglim);
        $baselim    = $hauteurlim / 2;

        $this->noeuds[1]->dessine($taille * 0.8);
        $imginf     = $this->noeuds[1]->image;
        $baseinf    = $this->noeuds[1]->base_verticale;
        $largeurinf = imagesx($imginf);
        $hauteurinf = imagesy($imginf);

        $this->noeuds[2]->dessine($taille);
        $imgexp     = $this->noeuds[2]->image;
        $baseexp    = $this->noeuds[2]->base_verticale;
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);

        $hauteur = $hauteurlim + $hauteurinf;
        $largeur = max($largeurinf, $largeurlim) + ceil($taille / 8);
        $imgfin  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc   = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc   = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($imgfin, $imglim, ($largeur - $largeurlim) / 2, 0, 0, 0, $largeurlim, $hauteurlim);
        imagecopy($imgfin, $imginf, ($largeur - $largeurinf) / 2, $hauteurlim, 0, 0, $largeurinf, $hauteurinf);

        $this->image          = MathPublisher::alignement2($imgfin, $baselim, $imgexp, $baseexp);
        $this->base_verticale = max($baselim, $baseexp);
    }

    /**
     * @param $taille
     */
    private function dessineRoot($taille)
    {
        $this->noeuds[1]->dessine($taille * 0.6);
        $imgroot     = $this->noeuds[1]->image;
        $baseroot    = $this->noeuds[1]->base_verticale;
        $largeurroot = imagesx($imgroot);
        $hauteurroot = imagesy($imgroot);

        $this->noeuds[2]->dessine($taille);
        $imgexp     = $this->noeuds[2]->image;
        $baseexp    = $this->noeuds[2]->base_verticale;
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);

        $imgrac     = MathPublisher::afficheSymbol('_racine', $hauteurexp + 2);
        $largeurrac = imagesx($imgrac);
        $hauteurrac = imagesy($imgrac);
        $baserac    = $hauteurrac / 2;

        $largeur = $largeurrac + $largeurexp;
        $hauteur = max($hauteurexp, $hauteurrac);
        $result  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($result, 0, 0, 0);
        $blanc   = imagecolorallocate($result, 255, 255, 255);
        $blanc   = imagecolortransparent($result, $blanc);
        imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($result, $imgrac, 0, 0, 0, 0, $largeurrac, $hauteurrac);
        imagecopy($result, $imgexp, $largeurrac, $hauteur - $hauteurexp, 0, 0, $largeurexp, $hauteurexp);
        imagesetthickness($result, 1);
        imageline($result, $largeurrac - 2, 2, $largeurrac + $largeurexp + 2, 2, $noir);
        imagecopy($result, $imgroot, 0, 0, 0, 0, $largeurroot, $hauteurroot);
        $this->base_verticale = $hauteur - $hauteurexp + $baseexp;
        $this->image          = $result;
    }

    /**
     * @param $taille
     */
    private function dessineFraction($taille)
    {
        $this->noeuds[0]->dessine($taille * 0.9);
        $img1  = $this->noeuds[0]->image;
        $base1 = $this->noeuds[0]->base_verticale;
        $this->noeuds[2]->dessine($taille * 0.9);
        $img2                 = $this->noeuds[2]->image;
        $base2                = $this->noeuds[2]->base_verticale;
        $hauteur1             = imagesy($img1);
        $hauteur2             = imagesy($img2);
        $largeur1             = imagesx($img1);
        $largeur2             = imagesx($img2);
        $largeur              = max($largeur1, $largeur2);
        $hauteur              = $hauteur1 + $hauteur2 + 4;
        $result               = imagecreate(max($largeur + 5, 1), max($hauteur, 1));
        $noir                 = imagecolorallocate($result, 0, 0, 0);
        $blanc                = imagecolorallocate($result, 255, 255, 255);
        $blanc                = imagecolortransparent($result, $blanc);
        $this->base_verticale = $hauteur1 + 2;
        imagefilledrectangle($result, 0, 0, $largeur + 4, $hauteur - 1, $blanc);
        imagecopy($result, $img1, ($largeur - $largeur1) / 2, 0, 0, 0, $largeur1, $hauteur1);
        imageline($result, 0, $this->base_verticale, $largeur, $this->base_verticale, $noir);
        imagecopy($result, $img2, ($largeur - $largeur2) / 2, $hauteur1 + 4, 0, 0, $largeur2, $hauteur2);
        $this->image = $result;
    }

    /**
     * @param $taille
     */
    private function dessineExposant($taille)
    {
        $this->noeuds[0]->dessine($taille);
        $img1  = $this->noeuds[0]->image;
        $base1 = $this->noeuds[0]->base_verticale;
        $this->noeuds[2]->dessine($taille * 0.8);
        $img2     = $this->noeuds[2]->image;
        $base2    = $this->noeuds[2]->base_verticale;
        $hauteur1 = imagesy($img1);
        $hauteur2 = imagesy($img2);
        $largeur1 = imagesx($img1);
        $largeur2 = imagesx($img2);
        $largeur  = $largeur1 + $largeur2;
        if ($hauteur1 >= $hauteur2) {
            $hauteur              = ceil($hauteur2 / 2 + $hauteur1);
            $this->base_verticale = $hauteur2 / 2 + $base1;
            $result               = imagecreate(max($largeur, 1), max($hauteur, 1));
            $noir                 = imagecolorallocate($result, 0, 0, 0);
            $blanc                = imagecolorallocate($result, 255, 255, 255);
            $blanc                = imagecolortransparent($result, $blanc);
            imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
            imagecopy($result, $img1, 0, ceil($hauteur2 / 2), 0, 0, $largeur1, $hauteur1);
            imagecopy($result, $img2, $largeur1, 0, 0, 0, $largeur2, $hauteur2);
        } else {
            $hauteur              = ceil($hauteur1 / 2 + $hauteur2);
            $this->base_verticale = $hauteur2 - $base1 + $hauteur1 / 2;
            $result               = imagecreate(max($largeur, 1), max($hauteur, 1));
            $noir                 = imagecolorallocate($result, 0, 0, 0);
            $blanc                = imagecolorallocate($result, 255, 255, 255);
            $blanc                = imagecolortransparent($result, $blanc);
            imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
            imagecopy($result, $img1, 0, ceil($hauteur2 - $hauteur1 / 2), 0, 0, $largeur1, $hauteur1);
            imagecopy($result, $img2, $largeur1, 0, 0, 0, $largeur2, $hauteur2);
        }
        $this->image = $result;
    }

    /**
     * @param $taille
     */
    private function dessineDessus($taille)
    {
        $this->noeuds[2]->dessine($taille * 0.8);
        $imgsup  = $this->noeuds[2]->image;
        $basesup = $this->noeuds[2]->base_verticale;
        $this->noeuds[0]->dessine($taille);
        $imgexp  = $this->noeuds[0]->image;
        $baseexp = $this->noeuds[0]->base_verticale;
//expression
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);
//bornesup
        $largeursup = imagesx($imgsup);
        $hauteursup = imagesy($imgsup);
//fin
        $hauteur = $hauteurexp + $hauteursup;
        $largeur = max($largeursup, $largeurexp) + ceil($taille / 8);
        $imgfin  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc   = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc   = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($imgfin, $imgsup, ($largeur - $largeursup) / 2, 0, 0, 0, $largeursup, $hauteursup);
        imagecopy($imgfin, $imgexp, ($largeur - $largeurexp) / 2, $hauteursup, 0, 0, $largeurexp, $hauteurexp);
        $this->image          = $imgfin;
        $this->base_verticale = $baseexp + $hauteursup;
    }

    /**
     * @param $taille
     */
    private function dessineIndice($taille)
    {
        $this->noeuds[0]->dessine($taille);
        $img1  = $this->noeuds[0]->image;
        $base1 = $this->noeuds[0]->base_verticale;
        $this->noeuds[2]->dessine($taille * 0.8);
        $img2     = $this->noeuds[2]->image;
        $base2    = $this->noeuds[2]->base_verticale;
        $hauteur1 = imagesy($img1);
        $hauteur2 = imagesy($img2);
        $largeur1 = imagesx($img1);
        $largeur2 = imagesx($img2);
        $largeur  = $largeur1 + $largeur2;
        if ($hauteur1 >= $hauteur2) {
            $hauteur              = ceil($hauteur2 / 2 + $hauteur1);
            $this->base_verticale = $base1;
            $result               = imagecreate(max($largeur, 1), max($hauteur, 1));
            $noir                 = imagecolorallocate($result, 0, 0, 0);
            $blanc                = imagecolorallocate($result, 255, 255, 255);
            $blanc                = imagecolortransparent($result, $blanc);
            imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
            imagecopy($result, $img1, 0, 0, 0, 0, $largeur1, $hauteur1);
            imagecopy($result, $img2, $largeur1, ceil($hauteur1 - $hauteur2 / 2), 0, 0, $largeur2, $hauteur2);
        } else {
            $hauteur              = ceil($hauteur1 / 2 + $hauteur2);
            $this->base_verticale = $base1;
            $result               = imagecreate(max($largeur, 1), max($hauteur, 1));
            $noir                 = imagecolorallocate($result, 0, 0, 0);
            $blanc                = imagecolorallocate($result, 255, 255, 255);
            $blanc                = imagecolortransparent($result, $blanc);
            imagefilledrectangle($result, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
            imagecopy($result, $img1, 0, 0, 0, 0, $largeur1, $hauteur1);
            imagecopy($result, $img2, $largeur1, ceil($hauteur1 / 2), 0, 0, $largeur2, $hauteur2);
        }
        $this->image = $result;
    }

    /**
     * @param $taille
     */
    private function dessineDessous($taille)
    {
        $this->noeuds[2]->dessine($taille * 0.8);
        $imginf  = $this->noeuds[2]->image;
        $baseinf = $this->noeuds[2]->base_verticale;
        $this->noeuds[0]->dessine($taille);
        $imgexp  = $this->noeuds[0]->image;
        $baseexp = $this->noeuds[0]->base_verticale;
//expression
        $largeurexp = imagesx($imgexp);
        $hauteurexp = imagesy($imgexp);
//borneinf
        $largeurinf = imagesx($imginf);
        $hauteurinf = imagesy($imginf);
//fin
        $hauteur = $hauteurexp + $hauteurinf;
        $largeur = max($largeurinf, $largeurexp) + ceil($taille / 8);
        $imgfin  = imagecreate(max($largeur, 1), max($hauteur, 1));
        $noir    = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc   = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc   = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeur - 1, $hauteur - 1, $blanc);
        imagecopy($imgfin, $imgexp, ($largeur - $largeurexp) / 2, 0, 0, 0, $largeurexp, $hauteurexp);
        imagecopy($imgfin, $imginf, ($largeur - $largeurinf) / 2, $hauteurexp, 0, 0, $largeurinf, $hauteurinf);
        $this->image          = $imgfin;
        $this->base_verticale = $baseexp;
    }

    /**
     * @param $taille
     * @param $caractere
     */
    private function dessineGrandoperateur($taille, $caractere)
    {
        $this->noeuds[1]->dessine($taille * 0.8);
        $img1  = $this->noeuds[1]->image;
        $base1 = $this->noeuds[1]->base_verticale;
        $this->noeuds[2]->dessine($taille * 0.8);
        $img2  = $this->noeuds[2]->image;
        $base2 = $this->noeuds[2]->base_verticale;
        $this->noeuds[3]->dessine($taille);
        $imgexp  = $this->noeuds[3]->image;
        $baseexp = $this->noeuds[3]->base_verticale;
//borneinf
        $largeur1 = imagesx($img1);
        $hauteur1 = imagesy($img1);
//bornesup
        $largeur2 = imagesx($img2);
        $hauteur2 = imagesy($img2);
//expression
        $hauteurexp = imagesy($imgexp);
        $largeurexp = imagesx($imgexp);
//caractere
        $imgsymbole     = MathPublisher::afficheSymbol($caractere, $baseexp * 1.8); //max($baseexp,$hauteurexp-$baseexp)*2);
        $largeursymbole = imagesx($imgsymbole);
        $hauteursymbole = imagesy($imgsymbole);
        $basesymbole    = $hauteursymbole / 2;

        $hauteurgauche = $hauteursymbole + $hauteur1 + $hauteur2;
        $largeurgauche = max($largeursymbole, $largeur1, $largeur2);
        $imggauche     = imagecreate(max($largeurgauche, 1), max($hauteurgauche, 1));
        $noir          = imagecolorallocate($imggauche, 0, 0, 0);
        $blanc         = imagecolorallocate($imggauche, 255, 255, 255);
        $blanc         = imagecolortransparent($imggauche, $blanc);
        imagefilledrectangle($imggauche, 0, 0, $largeurgauche - 1, $hauteurgauche - 1, $blanc);
        imagecopy($imggauche, $imgsymbole, ($largeurgauche - $largeursymbole) / 2, $hauteur2, 0, 0, $largeursymbole, $hauteursymbole);
        imagecopy($imggauche, $img2, ($largeurgauche - $largeur2) / 2, 0, 0, 0, $largeur2, $hauteur2);
        imagecopy($imggauche, $img1, ($largeurgauche - $largeur1) / 2, $hauteur2 + $hauteursymbole, 0, 0, $largeur1, $hauteur1);
        $imgfin               = MathPublisher::alignement2($imggauche, $basesymbole + $hauteur2, $imgexp, $baseexp);
        $this->image          = $imgfin;
        $this->base_verticale = max($basesymbole + $hauteur2, $baseexp + $hauteur2);
    }

    /**
     * @param $taille
     */
    private function dessineDelimiteur($taille)
    {
        $this->noeuds[2]->dessine($taille);
        $imgexp     = $this->noeuds[2]->image;
        $baseexp    = $this->noeuds[2]->base_verticale;
        $hauteurexp = imagesy($imgexp);
        if ($this->noeuds[1]->texte == '&$') {
            $imggauche = MathPublisher::parenthese($hauteurexp, $this->noeuds[1]->noeuds[0]->texte);
        } else {
            $imggauche = MathPublisher::parenthese($hauteurexp, $this->noeuds[1]->texte);
        }
        $basegauche = imagesy($imggauche) / 2;
        if ($this->noeuds[3]->texte == '&$') {
            $imgdroit = MathPublisher::parenthese($hauteurexp, $this->noeuds[3]->noeuds[0]->texte);
        } else {
            $imgdroit = MathPublisher::parenthese($hauteurexp, $this->noeuds[3]->texte);
        }
        $basedroit            = imagesy($imgdroit) / 2;
        $this->image          = MathPublisher::alignement3($imggauche, $basegauche, $imgexp, $baseexp, $imgdroit, $basedroit);
        $this->base_verticale = max($basegauche, $baseexp, $basedroit);
    }

    /**
     * @param $taille
     */
    private function dessineMatrice($taille)
    {
        $padding      = 8;
        $nbligne      = $this->noeuds[1]->noeuds[0]->texte;
        $nbcolonne    = $this->noeuds[2]->noeuds[0]->texte;
        $largeur_case = 0;
        $hauteur_case = 0;

        for ($ligne = 0; $ligne < $nbligne; $ligne++) {
            $hauteur_ligne[$ligne] = 0;
            $dessus_ligne[$ligne]  = 0;
        }
        for ($col = 0; $col < $nbcolonne; $col++) {
            $largeur_colonne[$col] = 0;
        }
        $i = 0;
        for ($ligne = 0; $ligne < $nbligne; $ligne++) {
            for ($col = 0; $col < $nbcolonne; $col++) {
                if ($i < count($this->noeuds[3]->noeuds)) {
                    $this->noeuds[3]->noeuds[$i]->dessine($taille * 0.9);
                    $img[$i]               = $this->noeuds[3]->noeuds[$i]->image;
                    $base[$i]              = $this->noeuds[3]->noeuds[$i]->base_verticale;
                    $dessus_ligne[$ligne]  = max($base[$i], $dessus_ligne[$ligne]);
                    $largeur[$i]           = imagesx($img[$i]);
                    $hauteur[$i]           = imagesy($img[$i]);
                    $hauteur_ligne[$ligne] = max($hauteur_ligne[$ligne], $hauteur[$i]);
                    $largeur_colonne[$col] = max($largeur_colonne[$col], $largeur[$i]);
                }
                $i++;
            }
        }

        $hauteurfin = 0;
        $largeurfin = 0;
        for ($ligne = 0; $ligne < $nbligne; $ligne++) {
            $hauteurfin += $hauteur_ligne[$ligne] + $padding;
        }
        for ($col = 0; $col < $nbcolonne; $col++) {
            $largeurfin += $largeur_colonne[$col] + $padding;
        }
        $hauteurfin -= $padding;
        $largeurfin -= $padding;
        $imgfin     = imagecreate(max($largeurfin, 1), max($hauteurfin, 1));
        $noir       = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc      = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc      = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeurfin - 1, $hauteurfin - 1, $blanc);
        $i = 0;
        $h = $padding / 2 - 1;
        for ($ligne = 0; $ligne < $nbligne; $ligne++) {
            $l = $padding / 2 - 1;
            for ($col = 0; $col < $nbcolonne; $col++) {
                if ($i < count($this->noeuds[3]->noeuds)) {
                    imagecopy($imgfin, $img[$i], $l + ceil($largeur_colonne[$col] - $largeur[$i]) / 2, $h + $dessus_ligne[$ligne] - $base[$i], 0, 0, $largeur[$i], $hauteur[$i]);
                    //ImageRectangle($imgfin,$l,$h,$l+$largeur_colonne[$col],$h+$hauteur_ligne[$ligne],$noir);
                }
                $l += $largeur_colonne[$col] + $padding;
                $i++;
            }
            $h += $hauteur_ligne[$ligne] + $padding;
        }
//ImageRectangle($imgfin,0,0,$largeurfin-1,$hauteurfin-1,$noir);
        $this->image          = $imgfin;
        $this->base_verticale = imagesy($imgfin) / 2;
    }

    /**
     * @param $taille
     */
    private function dessineTableau($taille)
    {
        $padding      = 8;
        $typeligne    = $this->noeuds[1]->noeuds[0]->texte;
        $typecolonne  = $this->noeuds[2]->noeuds[0]->texte;
        $nbligne      = strlen($typeligne) - 1;
        $nbcolonne    = strlen($typecolonne) - 1;
        $largeur_case = 0;
        $hauteur_case = 0;

        for ($ligne = 0; $ligne < $nbligne; $ligne++) {
            $hauteur_ligne[$ligne] = 0;
            $dessus_ligne[$ligne]  = 0;
        }
        for ($col = 0; $col < $nbcolonne; $col++) {
            $largeur_colonne[$col] = 0;
        }
        $i = 0;
        for ($ligne = 0; $ligne < $nbligne; $ligne++) {
            for ($col = 0; $col < $nbcolonne; $col++) {
                if ($i < count($this->noeuds[3]->noeuds)) {
                    $this->noeuds[3]->noeuds[$i]->dessine($taille * 0.9);
                    $img[$i]               = $this->noeuds[3]->noeuds[$i]->image;
                    $base[$i]              = $this->noeuds[3]->noeuds[$i]->base_verticale;
                    $dessus_ligne[$ligne]  = max($base[$i], $dessus_ligne[$ligne]);
                    $largeur[$i]           = imagesx($img[$i]);
                    $hauteur[$i]           = imagesy($img[$i]);
                    $hauteur_ligne[$ligne] = max($hauteur_ligne[$ligne], $hauteur[$i]);
                    $largeur_colonne[$col] = max($largeur_colonne[$col], $largeur[$i]);
                }
                $i++;
            }
        }

        $hauteurfin = 0;
        $largeurfin = 0;
        for ($ligne = 0; $ligne < $nbligne; $ligne++) {
            $hauteurfin += $hauteur_ligne[$ligne] + $padding;
        }
        for ($col = 0; $col < $nbcolonne; $col++) {
            $largeurfin += $largeur_colonne[$col] + $padding;
        }
        $imgfin = imagecreate(max($largeurfin, 1), max($hauteurfin, 1));
        $noir   = imagecolorallocate($imgfin, 0, 0, 0);
        $blanc  = imagecolorallocate($imgfin, 255, 255, 255);
        $blanc  = imagecolortransparent($imgfin, $blanc);
        imagefilledrectangle($imgfin, 0, 0, $largeurfin - 1, $hauteurfin - 1, $blanc);
        $i = 0;
        $h = $padding / 2 - 1;
        if (substr($typeligne, 0, 1) == '1') {
            imageline($imgfin, 0, 0, $largeurfin - 1, 0, $noir);
        }
        for ($ligne = 0; $ligne < $nbligne; $ligne++) {
            $l = $padding / 2 - 1;
            if (substr($typecolonne, 0, 1) == '1') {
                imageline($imgfin, 0, $h - $padding / 2, 0, $h + $hauteur_ligne[$ligne] + $padding / 2, $noir);
            }
            for ($col = 0; $col < $nbcolonne; $col++) {
                if ($i < count($this->noeuds[3]->noeuds)) {
                    imagecopy($imgfin, $img[$i], $l + ceil($largeur_colonne[$col] - $largeur[$i]) / 2, $h + $dessus_ligne[$ligne] - $base[$i], 0, 0, $largeur[$i], $hauteur[$i]);
                    if (substr($typecolonne, $col + 1, 1) == '1') {
                        imageline($imgfin, $l + $largeur_colonne[$col] + $padding / 2, $h - $padding / 2, $l + $largeur_colonne[$col] + $padding / 2, $h + $hauteur_ligne[$ligne] + $padding / 2, $noir);
                    }
                }
                $l += $largeur_colonne[$col] + $padding;
                $i++;
            }
            if (substr($typeligne, $ligne + 1, 1) == '1') {
                imageline($imgfin, 0, $h + $hauteur_ligne[$ligne] + $padding / 2, $largeurfin - 1, $h + $hauteur_ligne[$ligne] + $padding / 2, $noir);
            }
            $h += $hauteur_ligne[$ligne] + $padding;
        }
        $this->image          = $imgfin;
        $this->base_verticale = imagesy($imgfin) / 2;
    }
}
