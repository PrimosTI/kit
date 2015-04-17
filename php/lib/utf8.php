<?php
/*
UTF-8 Lib para PHP
Copyright (c) 2015 Primos Tecnologia da Informação Ltda.

É permitida a distribuição irrestrita desta obra, livre de taxas e
encargos, incluindo, e sem limitar-se ao uso, cópia, modificação,
combinação e/ou publicação, bem como a aplicação em outros trabalhos
derivados deste.

A mensagem de direito autoral:
    UTF-8 Lib para PHP
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
    Mail Parse Lib para PHP
    =====================================
    Versão:      0.1.0
    Criação:     2015-04-17
    Alteração:   2015-04-17
    
    Escrito por: Rodrigo Speller
    E-mail:      rspeller@primosti.com.br
    -------------------------------------

"UTF-8 Lib" é uma biblioteca para a análise, tratamento e manipulação de
dados (texto e caracteres) codificados no padrão UTF-8 baseados na
[RFC 3629].

Alterações
----------

» 0.1.0

- Lançamento para testes.

Referências
-----------

[RFC 3629] F. Yergeau, "UTF-8, a transformation format of ISO 10646",
           RFC 3629, Novembro/2003,
           <http://tools.ietf.org/html/rfc3629>.

*/

/**
 * Convert um valor Unicode em uma string UTF-8, conforme o formato
 * definido pela [RFC 3629].
 * 
 * @since 0.1.0
 *
 * @param int $code Um valor unicode válido.
 * @return string|false Retorna a string UTF-8 content o caratere
 * equivalente ao valor informado.
 */
function utf8_chr($code) {
    // code == negativo
    if ($code < 0)
        return false;
        
    if ($code < 0x80) {
        // 1 byte
        // 0000..007F
        return chr($code);
    } elseif ($code < 0x0800) {
        // 2 bytes
        // 0080..07FF
        return chr($code >> 6 | 0xC0)
          . chr($code & 0x3F | 0x80);
    } elseif ($code < 0x010000) {
        // 3 bytes
        // 0800..D7FF
        // D800..DFFF => UTF-16
        // E000..FFFF      
        if ($code >= 0xD800 && $code <= 0xDFFF)
            return false;
            
        return chr($code >> 12 | 0xE0)
          . chr($code >> 6 & 0x3F | 0x80)
          . chr($code & 0x3F | 0x80);
    } elseif ($code < 0x110000) {
        // 4 bytes
        // 010000..10FFFF
        return chr($code >> 18 | 0xF0)
          . chr($code >> 12 & 0x3F | 0x80)
          . chr($code >> 6 & 0x3F | 0x80)
          . chr($code & 0x3F | 0x80);
    }
    // code > 0x10FFFF
    return false;  
}

/**
 * Converte o valor Unicode do primeiro caractere expresso por uma
 * string UTF-8, conforme o formato definido pela [RFC 3629].
 * 
 * @since 0.1.0
 *
 * @param string $string A string UTF-8 iniciada pelo caractere a ser
 * testado.
 * @return int|false Retorna o valor Unicode do primeiro caractere da
 * string.
 */
function utf8_ord($string) {
    $st = ord($string[0]);

    if($st < 0x80) {
        // 1 byte
        // 0000..007F
        return $st;
    } elseif ($st < 0xC0) {
        // 10xxxxxx
        return false;
    } elseif ($st < 0xE0) {
        // 2 bytes
        // 0080..07FF
        $ret = (($st & 0x1F) << 6)
          | (ord($string[1]) & 0x3F);
          
        if($ret < 0x80)
            return false;
    } elseif ($st < 0xF0)  {
        // 3 bytes
        // 0800..D7FF
        // D800..DFFF => UTF-16
        // E000..FFFF      
        $ret = (($st & 0x0F) << 12) 
          | (ord($string[1]) & 0x3F) << 6 
          | (ord($string[2]) & 0x3F);
        
        if($ret < 0x800 || ($ret >= 0xD800 && $ret <= 0xDFFF)) 
            return false;
    } elseif ($st <= 0xF4) {
        // 4 bytes
        // 010000..10FFFF
        $ret = (($st & 0x0F) << 18) 
          | (ord($string[1]) & 0x3F) << 12  
          | (ord($string[2]) & 0x3F) << 6 
          | (ord($string[3]) & 0x3F);
          
        if($ret < 0x10000 || $ret > 0x10FFFF) 
            return false;
    } else {
        return false;
    }
    
    return $ret;
}

?>