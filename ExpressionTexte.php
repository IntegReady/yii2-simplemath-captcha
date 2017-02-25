<?php
/**
 * Created by PhpStorm.
 * User: Eugene
 * Date: 2/25/2017
 * Time: 2:59 PM
 */

namespace integready\simplemathcaptcha;

/**
 * Class ExpressionTexte
 * @package integready\simplemathcaptcha
 */
class ExpressionTexte extends Expression
{
    /**
     * @param string $exp
     */
    public function setExpressionTexte($exp)
    {
        $this->texte = $exp;
    }

    /**
     * @param int $taille
     */
    public function setDessine($taille)
    {
        $this->image          = MathPublisher::afficheMath($this->texte, $taille);
        $this->base_verticale = imagesy($this->image) / 2;
    }
}
