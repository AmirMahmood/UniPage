<?php

/*
    simple db migration
*/

use Doctrine\DBAL\Connection;
use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use UniPage\Middleware\LoginMiddleware;

function v1($conn)
{
    $q1 = "
    CREATE TABLE `app_info` (
      `id` int NOT NULL,
      `db_version` int NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    $q2 = "
    ALTER TABLE `app_info`
      ADD PRIMARY KEY (`id`);
    ";

    $q3 = "
    ALTER TABLE `app_info`
      MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
    ";

    $q4 = "
    INSERT INTO `app_info` (`db_version`) VALUES (1);
    ";

    $conn->executeQuery($q1);
    $conn->executeQuery($q2);
    $conn->executeQuery($q3);
    $conn->executeQuery($q4);

    v1_2($conn);
};

function v1_2($conn)
{
    $q1 = "
    CREATE TABLE `user` (
        `id` int NOT NULL,
        `username` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
        `password` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `firstname` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
        `lastname` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
        `email` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
        `start_date` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `end_date` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `position` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
        `status` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
        `is_admin` tinyint(1) NOT NULL,
        `deleted` tinyint(1) NOT NULL,
        `last_login` datetime DEFAULT NULL,
        `last_modification` datetime DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    $q2 = "
    ALTER TABLE `user`
    ADD PRIMARY KEY (`id`),
    ADD UNIQUE KEY `username` (`username`),
    ADD UNIQUE KEY `email` (`email`);
    ";

    $q3 = "
    ALTER TABLE `user`
    MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
    ";

    $q4 = "
    INSERT INTO `user` (`id`, `username`, `password`, `firstname`, `lastname`, `email`, `start_date`, `end_date`, `position`, `status`, `is_admin`, `deleted`, `last_login`, `last_modification`) VALUES
(1, 'root', '4813494d137e1631bba301d5acab6e7bb7aa74ce1185d456565ef51d737677b2', 'root', 'root', 'root@root.root', '1111-11-11', '', 'other', 'active', 1, 0, NULL, NULL);
    ";

    $conn->executeQuery($q1);
    $conn->executeQuery($q2);
    $conn->executeQuery($q3);
    $conn->executeQuery($q4);
};

function v2($conn)
{
    $q1 = "
    CREATE TABLE `link` (
        `id` int NOT NULL,
        `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `type` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
        `last_modification` datetime DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    $q2 = "
    ALTER TABLE `link`
    ADD PRIMARY KEY (`id`);
    ";

    $q3 = "
    ALTER TABLE `link`
    MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
    ";

    $conn->executeQuery($q1);
    $conn->executeQuery($q2);
    $conn->executeQuery($q3);
}

function v3($conn)
{
    $q1 = "
    ALTER TABLE `user` DROP INDEX `email`;
    ";

    $conn->executeQuery($q1);
}

function v4($conn)
{
    $q1 = "
    ALTER TABLE `user`
    ADD `linkedin` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    ADD `google_scholar` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    ADD `researchgate` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    ADD `orcid` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    ADD `dblp` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
    ADD `website` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;
    ";

    $conn->executeQuery($q1);
}

function v5($conn)
{
    $q1 = "
    CREATE TABLE `publication` (
        `id` int NOT NULL,
        `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `title` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `published_in` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `authors` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `citation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `year` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        `type` varchar(16) COLLATE utf8mb4_general_ci NOT NULL,
        `last_modification` datetime DEFAULT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

    $q2 = "
    ALTER TABLE `publication`
    ADD PRIMARY KEY (`id`);
    ";

    $q3 = "
    ALTER TABLE `publication`
    MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
    ";

    $conn->executeQuery($q1);
    $conn->executeQuery($q2);
    $conn->executeQuery($q3);
}

function v6($conn)
{
    $q1 = "ALTER TABLE `user` ADD `bio` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;";
    $conn->executeQuery($q1);
}


function update_db_version($conn, int $ver)
{
    $q = "
    UPDATE `app_info` SET db_version = :db_version WHERE id=1 ;
    ";
    $stmt = $conn->prepare($q);
    $stmt->executeQuery(array('db_version' => $ver));
};

function run($conn, $func, int $ver)
{
    // $conn->beginTransaction();
    // try {
    call_user_func($func, $conn);
    update_db_version($conn, $ver);

    //     $conn->commit();
    // } catch (Exception $e) {
    //     $conn->rollBack();
    //     throw $e;
    // }
}

function run_upgrade($conn, $db_version)
{
    $runs = [];

    $last_version = 6;
    if ($db_version >= 1  && $db_version < $last_version) {
        foreach (range($db_version + 1, $last_version) as $ver) {
            array_push($runs, "v{$ver}");
            run($conn, "v{$ver}", $ver);
        }
    }

    return join(" ", $runs);
}

return function (App $app) {
    $app->get('/admin/install', function (Request $request, Response $response, $args) {
        $conn = $this->get(Connection::class);

        $schemaManager = $conn->getSchemaManager();
        if ($schemaManager->tablesExist(array('app_info')) != true) {
            run($conn, 'v1', 1);
            run_upgrade($conn, 1);
        }

        $response->getBody()->write("Ok.");
        return $response;
    });

    $app->get('/admin/upgrade', function (Request $request, Response $response, $args) {
        $conn = $this->get(Connection::class);

        $schemaManager = $conn->getSchemaManager();
        if ($schemaManager->tablesExist(array('user')) != true || $schemaManager->tablesExist(array('app_info')) != true) {
            return;
        }

        $db_version = $conn->query('select db_version from app_info where app_info.id = 1')
            ->fetchAll()[0]['db_version'];

        $runs_str = run_upgrade($conn, $db_version);

        $res_txt = "There is no database upgrade";
        if (!empty($runs_str)) {
            $res_txt = "Database upgrading done. upgrades: $runs_str";
        }

        $response->getBody()->write($res_txt);
        return $response;
    })->add(new LoginMiddleware($app->getContainer()));
};
