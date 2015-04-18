<?php
/*
Punycode Lib para PHP
Copyright (c) 2015 Primos Tecnologia da Informação Ltda.

É permitida a distribuição irrestrita desta obra, livre de taxas e
encargos, incluindo, e sem limitar-se ao uso, cópia, modificação,
combinação e/ou publicação, bem como a aplicação em outros trabalhos
derivados deste.

A mensagem de direito autoral:
    Punycode Lib para PHP
    Copyright (c) 2015 Primos Tecnologia da Informação Ltda.
deverá ser incluída em todas as cópias ou partes do obra derivado que
permitam a inclusão desta informação.

O autor desta obra poderá alterar as condições de licenciamento em
versões futuras, porém, tais alterações serão vigentes somente a partir
da versão alterada.

O LICENCIANTE OFERECE A OBRA “NO ESTADO EM QUE SE ENCONTRA” (AS IS) E
NÃO PRESTA QUAISQUER GARANTIAS OU DECLARAÇÕES DE QUALQUER ESPÉCIE
RELATIVAS À ESTA OBRA, SEJAM ELAS EXPRESSAS OU IMPLÍCITAS, DECORRENTES
DA LEI OU QUAISQUER OUTRAS, INCLUINDO, SEM LIMITAÇÃO, QUAISQUER
GARANTIAS SOBRE A TITULARIDADE, ADEQUAÇÃO PARA QUAISQUER PROPÓSITOS,
NÃO-VIOLAÇÃO DE DIREITOS, OU INEXISTÊNCIA DE QUAISQUER DEFEITOS
LATENTES, ACURACIDADE, PRESENÇA OU AUSÊNCIA DE ERROS, SEJAM ELES
APARENTES OU OCULTOS. REVOGAM-SE AS PERMISSÕES DESTA LICENÇA EM
JURISDIÇÕES QUE NÃO ACEITEM A EXCLUSÃO DE GARANTIAS IMPLÍCITAS.

EM NENHUMA CIRCUNSTÂNCIA O LICENCIANTE SERÁ RESPONSÁVEL PARA COM VOCÊ
POR QUAISQUER DANOS, ESPECIAIS, INCIDENTAIS, CONSEQÜENCIAIS, PUNITIVOS
OU EXEMPLARES, ORIUNDOS DESTA LICENÇA OU DO USO DESTA OBRA, MESMO QUE O
LICENCIANTE TENHA SIDO AVISADO SOBRE A POSSIBILIDADE DE TAIS DANOS.

[PT]
*** AVISO LEGAL ***
Antes de usar esta obra, você deve entender e concordar com os termos
acima.

[ES]
*** AVISO LEGAL ***
Antes de usar este trabajo, debe entender y aceptar las condiciones
anteriores.

[EN]
*** LEGAL NOTICE ***
You must understand and agree to the above terms before using this work.

[CH]
*** 法律聲明 ***
使用這項工作之前，了解並同意本許可。

[JP]
*** 法律上の注意事項 ***
この作品を使用する前に、理解し、このライセンスに同意する。

*/

/*
    =====================================
    Punycode Lib para PHP
    =====================================
    Versão:      0.1.0
    Criação:     2015-04-18
    Alteração:   2015-04-18

    Escrito por: Rodrigo Speller
    E-mail:      rspeller@primosti.com.br
    -------------------------------------

"Punycode Lib" é uma biblioteca para a codificação e decodificação de
cadeias de caracters entre os padrões Punycode e UTF-8, de acordo com a
[RFC 3492].

Punycode é uma sintaxe para transferência de codificação desenvolvida
para ser usada com “Internationalized Domain Names in Applications”
(IDNA). Ela transforma uma string Unicode em um string ASCII de forma
exclusiva e reversível, permitindo a expansão do uso de caracteres em
rótulos de hostnames (letras, dígitos e hífens).

Alterações
----------

» 0.1.0

- Lançamento para testes.

Referências
-----------

[RFC 3492] A. Costello, "Punycode: A Bootstring encoding of Unicode for
           Internationalized Domain Names in Applications (IDNA)",
           RFC 3492, Março/2003, <http://tools.ietf.org/html/rfc3492>.

*/

require_once 'utf8.php';

/* Parâmetros Bootstring para o Punycode */
const PUNYCODE_BASE = 36;
const PUNYCODE_TMIN = 1;
const PUNYCODE_TMAX = 26;
const PUNYCODE_SKEW = 38;
const PUNYCODE_DAMP = 700;
const PUNYCODE_INITIAL_BIAS = 72;
const PUNYCODE_INITIAL_N = 0x80;
const PUNYCODE_DELIMITER = '-';

const IDNA_PUNYCODE_PREFIX = 'xn--';

/**
 * Convert um valor de caractere Punicode em uma string ASCII.
 *
 * @since 0.1.0
 *
 * @param int $codepoint Um valor de caractere Punicode válido.
 * @return string|false Retorna um string contendo o caratere
 * equivalente ao valor informado.
 */
function punycode_chr($codepoint) {
    if($codepoint >= 0 && $codepoint <= 25)
        return chr($codepoint + 0x61);

    if($codepoint >= 26 && $codepoint <= 35)
        return chr($codepoint + 0x16);

    return false;
}

/**
 * Obtêm o valor de caractere Punycode do primeiro caractere expresso
 * por uma string Punycode.
 *
 * @since 0.1.0
 *
 * @param string $string A string Punycode iniciada pelo caractere a ser
 * testado.
 * @return int|false Retorna o valor de caractere Punicode.
 */
function punycode_ord($char) {
    if(!isset($char[0]))
        return false;

    $char = ord($char[0]);

    if($char >= 0x61 && $char <= 0x7A)
        return $char - 0x61;

    if($char >= 0x30 && $char <= 0x39)
        return $char - 0x16;

    return false;
}

/**
 * Realiza a correção de um valor de "threshold" conforme os limites
 * definidos nos parâmetros do Punycode.
 *
 * @since 0.1.0
 *
 * @param int $t Valor "threshold" original.
 * @return int Valor "threshold" corrigido.
 */
function punycode_clamp_threshold($t) {
    if ($t < PUNYCODE_TMIN)
        return PUNYCODE_TMIN;
    elseif ($t > PUNYCODE_TMAX)
        return PUNYCODE_TMAX;
    return $t;
}

/**
 * Função para adaptação do viés (bias).
 *
 * @since 0.1.0
 *
 * @param int $delta
 * @param int $numpoints
 * @param bool $firsttime
 * @return int
 */
function punycode_adapt($delta, $numpoints, $firsttime) {
    $delta = (int)($delta / ($firsttime ? PUNYCODE_DAMP : 2));
    $delta += (int)($delta / $numpoints);

    $k = 0;

    $CONDITION =
        (int)(((PUNYCODE_BASE - PUNYCODE_TMIN) * PUNYCODE_TMAX) / 2);
    $DELTA_DIV = PUNYCODE_BASE - PUNYCODE_TMIN;

    while ($delta > $CONDITION) {
        $delta = (int)($delta / $DELTA_DIV);
        $k += PUNYCODE_BASE;
    }
    return $k
        + (int)(($DELTA_DIV + 1) * $delta / ($delta + PUNYCODE_SKEW));
}

/**
 * Decodifica uma string Punycode, retornando a string UTF-8
 * equivalente.
 *
 * @since 0.1.0
 *
 * @param string $input String em formato Punycode.
 * @return string|false String em formato UTF-8.
 */
function punycode_decode($input) {
    $inlen      = strlen($input);

    $output     = array();
    $outlen     = 0;

    $n      = PUNYCODE_INITIAL_N;
    $i      = 0;
    $bias   = PUNYCODE_INITIAL_BIAS;

    if($pos = (int)strrpos($input, PUNYCODE_DELIMITER)) {
        $output = substr($input, 0, $pos++);
        $outlen = strlen($output);
        $output = str_split($output);
    }

    while ($pos < $inlen) {
        $oldi = $i;
        $w = 1;

        $k = 0;
        while($k += PUNYCODE_BASE) {
            if($digit = punycode_ord($input[$pos++]) === false)
                return false;

            $i += $digit * $w;
            $t = punycode_clamp_threshold($k - $bias);

            if ($digit < $t)
                break;

            $w *= PUNYCODE_BASE - $t;
        }

        $bias = punycode_adapt($i - $oldi, ++$outlen, $oldi == 0);

        $n += (int)($i / $outlen);
        $i %= $outlen;

        $output = array_merge(
            array_splice($output, 0, $i),
            array(utf8_chr($n)),
            $output
        );

        $i++;
    }

    return implode('', $output);
}

/**
 * Codifica uma string UTF-8, retornando a string Punycode equivalente.
 *
 * @since 0.1.0
 *
 * @param string $input String em formato UTF-8.
 * @return string|false String em formato Punycode.
 */
function punycode_encode($input) {
    $output = array();
    global $PUNYCODE_BASIC_TABLE;
    /**/
    $inlen = strlen($input);

    $i = 0;
    $c = 0;
    $codepoints = array();
    $nbasic_codepoints = array();
    while (($code = utf8_ord(substr($input, $i, 4), $c)) !== false) {
        $char = substr($input, $i, $c);
        if($code < PUNYCODE_INITIAL_N)
            $output[] = $char;
        elseif(!in_array($code, $nbasic_codepoints))
            $nbasic_codepoints[] = $code;
        $codepoints[] = $code;

        $i += $c;
    }

    if ($i !== $inlen)
        return false;

    sort($nbasic_codepoints);
    /**/

    $inlen = count($codepoints);

    $n = PUNYCODE_INITIAL_N;
    $delta = 0;
    $bias = PUNYCODE_INITIAL_BIAS;

    if (($h = $b = count($output)) < $inlen)
        $output[] = PUNYCODE_DELIMITER;

    $i = 0;
    while ($h < $inlen) {
        $m = $nbasic_codepoints[$i++];
        $delta += ($m - $n) * ($h + 1);
        $n = $m;
        foreach ($codepoints as $c) {
            if ($c < $n)
                $delta++;
            elseif ($c == $n) {
                $q = $delta;
                $k = 0;
                while ($k += PUNYCODE_BASE) {
                    $t = punycode_clamp_threshold($k - $bias);

                    if ($q < $t)
                        break;

                    $output[] = punycode_chr(
                        $t + (($q - $t) % (PUNYCODE_BASE - $t)));

                    $q = (int)(($q - $t) / (PUNYCODE_BASE - $t));
                }
                $output[] = punycode_chr($q);
                $bias = punycode_adapt($delta, $h + 1, $h == $b);
                $delta = 0;
                $h++;
            }
        }
        $delta++;
        $n++;
    }

    return implode('', $output);
}

?>