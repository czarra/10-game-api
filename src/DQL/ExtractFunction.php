<?php

declare(strict_types=1);

namespace App\DQL; // Adjust namespace as per your project structure

use Doctrine\ORM\Query\AST;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

final class ExtractFunction extends FunctionNode
{
    public string $field; // Changed from Node to string
    public Node $expression;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER); // Matches the function name, e.g., "EXTRACT"
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $parser->match(TokenType::T_IDENTIFIER); // Matches the field name (e.g., EPOCH)
        $this->field = $parser->getLexer()->token->value;
        $parser->match(TokenType::T_FROM); // Matches the "FROM" keyword
        $this->expression = $parser->ArithmeticPrimary(); // Parses the date/time expression
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'EXTRACT(' .
            $this->field .
            ' FROM ' .
            $this->expression->dispatch($sqlWalker) .
            ')';
    }
}
