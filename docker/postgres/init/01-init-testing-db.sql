-- Initialisation de la base de données de test dédiée pour PostgreSQL 16
SELECT 'CREATE DATABASE billing_test'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'billing_test')\gexec

GRANT ALL PRIVILEGES ON DATABASE billing_test TO billing_user;
