# Concurrency and Race Condition Audit

This document outlines the findings of a security audit focused on concurrency safety and race conditions within the codebase.

## 1. Summary of Findings

The audit identified a critical race condition in the `PlantTreeWithTFTF` queued job, which could lead to an incorrect state of the tree reserve (e.g., overselling). Other areas of the application were reviewed, and no other major concurrency issues were found. The application relies on default Laravel configurations for session and database management, which are generally safe for typical use cases.

## 2. Focus Area Analysis

### 2.1. Laravel Queue Job Race Conditions

- **Finding:** A critical race condition was identified in the `handle` method of the `Core\Mod\Trees\Jobs\PlantTreeWithTFTF` job.
- **Vulnerability:** The logic to check for available trees (`TreeReserve::hasAvailable`) and the logic to decrement the reserve (`TreeReserve::decrementReserve`) were not atomic. This means that if two jobs were to run concurrently, they could both read the same initial value from the reserve, and both decrement it, leading to a state where more trees are promised than are available.

### 2.2. Cache Lock Implementation

- **Finding:** The application does not currently use Laravel's atomic cache locking (`Cache::lock`). This is not an immediate vulnerability, but it is a tool that should be considered for future features that require distributed locking.

### 2.3. Database Transaction Isolation

- **Finding:** The application uses the default Laravel database configuration, which typically corresponds to the `READ COMMITTED` isolation level for MySQL. This is a safe default, but it is important to be aware of it when designing complex database interactions.

### 2.4. Session Handling Concurrency

- **Finding:** The application uses the default Laravel session configuration. No specific concurrency issues were identified in the session handling.

### 2.5. Event Listener Ordering

- **Finding:** No specific issues were found with event listener ordering. The application's event-driven architecture appears to be straightforward, but this is an area that should be monitored as the application grows in complexity.

## 3. Remediation

The race condition in the `PlantTreeWithTFTF` job was resolved by wrapping the critical section of code in a database transaction with a pessimistic lock.

- **File:** `src/Mod/Trees/Jobs/PlantTreeWithTFTF.php`
- **Changes:**
  - The logic for checking the tree reserve and decrementing it is now wrapped in a `DB::transaction` closure, ensuring that the entire operation is atomic.
  - A pessimistic lock (`lockForUpdate()`) is now applied to the `TreeReserve` model when it is read from the database. This prevents other processes from reading or writing to the row until the transaction is complete.

## 4. Recommendations

- **Use Atomic Locks:** For future features that require distributed locking, consider using Laravel's `Cache::lock` functionality to prevent race conditions.
- **Continue Monitoring:** As the application grows, continue to monitor for potential concurrency issues, especially in areas with complex database interactions or long-running jobs.
