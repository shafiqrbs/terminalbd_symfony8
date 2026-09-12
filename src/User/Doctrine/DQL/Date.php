<?php
/**
 * Created by PhpStorm.
 * User: shafiq
 * Date: 2/5/18
 * Time: 12:45 PM
 */
namespace App\User\Doctrine\DQL;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\TokenType;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

class Date extends FunctionNode
{
    public $date;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return "DATE(" . $sqlWalker->walkArithmeticPrimary($this->date) . ")";
    }
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->date = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
