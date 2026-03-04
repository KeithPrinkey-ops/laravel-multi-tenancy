<?php

namespace Worldesports\MultiTenancy\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Worldesports\MultiTenancy\Models\Tenant;
use Worldesports\MultiTenancy\Models\TenantDatabase;

class CreateTenantCommand extends Command
{
    public $signature = 'tenant:create
                        {user_id : The user ID to assign the tenant to}
                        {name : The tenant name}
                        {--db-name= : Database name for the tenant}
                        {--db-host=127.0.0.1 : Database host}
                        {--db-port=3306 : Database port}
                        {--db-username= : Database username}
                        {--db-password= : Database password}
                        {--db-driver=mysql : Database driver}
                        {--create-db : Create the database if it doesn\'t exist}';

    public $description = 'Create a new tenant with database connection';

    public function handle(): int
    {
        $userId = $this->argument('user_id');
        $tenantName = $this->argument('name');

        // Validate user exists
        $userModel = config('multi-tenancy.user_model');
        if (!$userModel::find($userId)) {
            $this->error("User with ID {$userId} not found.");
            return self::FAILURE;
        }

        // Check if tenant already exists for user
        if (Tenant::where('user_id', $userId)->exists()) {
            $this->error("Tenant already exists for user ID {$userId}.");
            return self::FAILURE;
        }

        // Get database connection details
        $dbName = $this->option('db-name') ?? "tenant_{$userId}_db";
        $dbHost = $this->option('db-host');
        $dbPort = $this->option('db-port');
        $dbUsername = $this->option('db-username');
        $dbPassword = $this->option('db-password');
        $dbDriver = $this->option('db-driver');

        if (!$dbUsername || !$dbPassword) {
            $this->error('Database username and password are required.');
            return self::FAILURE;
        }

        // Validate connection details
        $validator = Validator::make([
            'driver' => $dbDriver,
            'host' => $dbHost,
            'port' => $dbPort,
            'database' => $dbName,
            'username' => $dbUsername,
            'password' => $dbPassword,
        ], [
            'driver' => 'required|string',
            'host' => 'required|string',
            'port' => 'required|integer',
            'database' => 'required|string',
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->line("  - {$error}");
            }
            return self::FAILURE;
        }

        try {
            // Create database if requested
            if ($this->option('create-db')) {
                $this->info('Creating database...');
                $this->createDatabase($dbHost, $dbPort, $dbUsername, $dbPassword, $dbName, $dbDriver);
            }

            // Test connection
            $this->info('Testing database connection...');
            $this->testConnection($dbHost, $dbPort, $dbUsername, $dbPassword, $dbName, $dbDriver);

            // Create tenant
            $this->info('Creating tenant...');
            $tenant = Tenant::create([
                'user_id' => $userId,
                'name' => $tenantName,
            ]);

            // Create tenant database
            $tenantDatabase = TenantDatabase::create([
                'tenant_id' => $tenant->id,
                'name' => $dbName,
                'connection_details' => [
                    'driver' => $dbDriver,
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'database' => $dbName,
                    'username' => $dbUsername,
                    'password' => $dbPassword,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                ],
            ]);

            $this->info("✅ Tenant '{$tenantName}' created successfully!");
            $this->info("   - Tenant ID: {$tenant->id}");
            $this->info("   - Database: {$dbName}");
            $this->info("   - User ID: {$userId}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to create tenant: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    private function createDatabase(string $host, int $port, string $username, string $password, string $database, string $driver): void
    {
        $config = [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];

        $connection = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME) === $driver
            ? DB::connection()
            : DB::connection()->setPdo(new \PDO(
                "{$driver}:host={$host};port={$port}",
                $username,
                $password
            ));

        $connection->statement("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    private function testConnection(string $host, int $port, string $username, string $password, string $database, string $driver): void
    {
        $dsn = "{$driver}:host={$host};port={$port};dbname={$database}";
        $pdo = new \PDO($dsn, $username, $password);
        $pdo->query('SELECT 1');
    }

    private function validateDatabaseConnection(array $connectionDetails): bool
    {
        try {
            // Test connection without database first (for creation)
            $dsn = "{$connectionDetails['driver']}:host={$connectionDetails['host']};port={$connectionDetails['port']}";
            $pdo = new \PDO($dsn, $connectionDetails['username'], $connectionDetails['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $this->info('✅ Database server connection successful');
            return true;
        } catch (\PDOException $e) {
            $this->error("❌ Database connection failed: {$e->getMessage()}");
            return false;
        }
    }

    private function validateDatabaseName(string $dbName): bool
    {
        // Validate database name format
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbName)) {
            $this->error('Database name can only contain letters, numbers, and underscores');
            return false;
        }

        if (strlen($dbName) > 64) {
            $this->error('Database name cannot exceed 64 characters');
            return false;
        }

        return true;
    }

    private function checkDatabaseExists(array $connectionDetails): bool
    {
        try {
            $dsn = "{$connectionDetails['driver']}:host={$connectionDetails['host']};port={$connectionDetails['port']}";
            $pdo = new \PDO($dsn, $connectionDetails['username'], $connectionDetails['password']);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$connectionDetails['database']]);

            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            $this->error("❌ Failed to check database existence: {$e->getMessage()}");
            return false;
        }
    }
}
