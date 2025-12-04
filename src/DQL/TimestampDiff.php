<?php

declare(strict_types=1);

namespace App\DQL;

use Doctrine\ORM\Query\AST\ASTException;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

/**
 * Class TimestampDiff
 *
 * Adds the a DQL function called TIMESTAMPDIFF(unit, datetime_expr1, datetime_expr2)
 *
 * The unit argument specifies the unit in which the difference between the two datetime expressions
 * is to be returned. This function is designed to be compatible with PostgreSQL.
 *
 * Example: TIMESTAMPDIFF(SECOND, u.createdAt, u.updatedAt)
 */
final class TimestampDiff extends FunctionNode
{
    private const SUPPORTED_UNITS = [
        'SECOND',
    ];

    public string $unit;
    public Node $datetimeExpr1;
    public Node $datetimeExpr2;

    /**
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $parser->match(TokenType::T_IDENTIFIER);
        $this->unit = $parser->getLexer()->token->value;
        $this->validateUnit();

        $parser->match(TokenType::T_COMMA);

        $this->datetimeExpr1 = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->datetimeExpr2 = $parser->ArithmeticPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    /**
     * @throws ASTException
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        $date1 = $this->datetimeExpr1->dispatch($sqlWalker);
        $date2 = $this->datetimeExpr2->dispatch($sqlWalker);

        if ($platform instanceof PostgreSQLPlatform) {
            if ($this->unit === 'SECOND') {
                return sprintf('EXTRACT(EPOCH FROM (%s - %s))', $date2, $date1);
            }
        }
        
        // Fallback or for other databases like MySQL
        return sprintf('TIMESTAMPDIFF(%s, %s, %s)', $this->unit, $date1, $date2);
    }

    /**
     * @throws QueryException
     */
    private function validateUnit(): void
    {
        if (!in_array($this->unit, self::SUPPORTED_UNITS, true)) {
            throw new QueryException(sprintf(
                'Unit "%s" is not supported. Supported units: "%s"',
                $this->unit,
                implode(', ', self::SUPPORTED_UNITS)
            ));
        }
    }
}
