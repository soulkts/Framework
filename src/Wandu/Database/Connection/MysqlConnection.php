<?php
namespace Wandu\Database\Connection;

use Doctrine\Common\Annotations\Reader;
use Exception;
use ArrayAccess;
use PDO;
use PDOStatement;
use Throwable;
use Wandu\Database\Contracts\ConnectionInterface;
use Wandu\Database\Contracts\QueryInterface;
use Wandu\Database\Exception\ClassNotFoundException;
use Wandu\Database\QueryBuilder;
use Wandu\Database\Repository\Repository;
use Wandu\Database\Repository\RepositorySettings;

class MysqlConnection implements ConnectionInterface
{
    /** @var \PDO */
    protected $pdo;

    /** @var \ArrayAccess */
    protected $container;

    /** @var string */
    protected $prefix;

    /**
     * @param \PDO $pdo
     * @param \ArrayAccess $container
     * @param string $prefix
     */
    public function __construct(PDO $pdo, ArrayAccess $container = null, $prefix = '')
    {
        $this->pdo = $pdo;
        $this->container = $container;
        $this->prefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueryBuilder($table)
    {
        return new QueryBuilder($this->getPrefix() . $table);
    }

    /**
     * {@inheritdoc}
     */
    public function createRepository($className)
    {
        if (!$this->container || !$this->container[Reader::class]) {
            throw new ClassNotFoundException(Reader::class);
        }
        return new Repository($this, RepositorySettings::fromAnnotation($className, $this->container[Reader::class]));
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($query, array $bindings = [])
    {
        $statement = $this->prepare($query, $bindings);
        $statement->execute();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function first($query, array $bindings = [])
    {
        $statement = $this->prepare($query, $bindings);
        $statement->execute();
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * {@inheritdoc}
     */
    public function query($query, array $bindings = [])
    {
        $statement = $this->prepare($query, $bindings);
        $statement->execute();
        return $statement->rowCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function transaction(callable $handler)
    {
        $this->pdo->beginTransaction();
        try {
            call_user_func($handler, $this);
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        $this->pdo->commit();
    }

    /**
     * @param string|callable|\Wandu\Database\Contracts\QueryInterface $query
     * @param array $bindings
     * @return \PDOStatement
     */
    protected function prepare($query, array $bindings = [])
    {
        while (is_callable($query)) {
            $query = call_user_func($query);
        }
        if ($query instanceof QueryInterface) {
            $bindings = $query->getBindings();
            $query = $query->toSql();
        }
        $statement = $this->pdo->prepare($query);
        $this->bindValues($statement, $bindings);
        return $statement;
    }

    /**
     * @param \PDOStatement $statement
     * @param array $bindings
     */
    protected function bindValues(PDOStatement $statement, array $bindings = [])
    {
        foreach ($bindings as $key => $value) {
            if (is_int($value)) {
                $dataType = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $dataType = PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $dataType = PDO::PARAM_NULL;
            } else {
                $dataType = PDO::PARAM_STR;
            }
            $statement->bindValue(
                is_int($key) ? $key + 1 : $key,
                $value,
                $dataType
            );
        }
    }
}
