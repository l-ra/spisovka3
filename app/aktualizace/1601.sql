/**
 * Patří k 1600.sql. Rozděleno do dvou kroků kvůli transakcím.
 */
UPDATE [epodatelna] SET [odeslano_dne] = [prijato_dne] WHERE [odchozi];

UPDATE [epodatelna] SET [prijato_dne] = NULL, [doruceno_dne] = NULL WHERE [odchozi];