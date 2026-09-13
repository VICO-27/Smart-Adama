# Revised Voyage Migration Plan

## 1. Selected Voyage Model
Based on the current official documentation and your candidate suggestion, we will use **`voyage-4`**.
- **Model:** `voyage-4`
- **Retrieval Context:** We will implement provider-specific configuration so that indexing uses `input_type=document` and runtime retrieval uses `input_type=query`.

## 2. Vector Dimension
- **Dimension:** `1024`

## 3. Database Structure (Option B)
We will create a new, dedicated table for embeddings linked to the core chunks, allowing infinite provider coexistence without duplicating chunk text.

```sql
CREATE TABLE content_chunk_embeddings (
    id UUID PRIMARY KEY,
    chunk_id UUID REFERENCES content_chunks(id) ON DELETE CASCADE,
    provider VARCHAR(50) NOT NULL, -- e.g., 'voyage', 'ollama'
    model VARCHAR(100) NOT NULL,   -- e.g., 'voyage-4', 'mxbai-embed-large'
    dimension INTEGER NOT NULL,
    embedding vector(1024) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(chunk_id, provider, model)
);
```
*Note: The existing `embedding` column in `content_chunks` will be preserved so we do not break any immediate legacy local dependencies, but all future RAG queries will route through the new table.*

## 4. Index Structure
To ensure production retrieval is completely isolated and lightning-fast, we will use a **partial HNSW index** over the new table, restricted strictly to Voyage vectors.

```sql
CREATE INDEX content_chunk_embeddings_voyage_hnsw_idx
ON content_chunk_embeddings USING hnsw (embedding vector_cosine_ops)
WHERE provider = 'voyage';
```
This guarantees that Ollama vectors (or any future embeddings) will not pollute or slow down the Voyage index.

## 5. Migration Strategy
1. **Schema Update:** Run a Laravel migration to create the `content_chunk_embeddings` table and the partial HNSW index.
2. **Code Updates:**
   - Update `VoyageEmbeddingProvider` to support `input_type`.
   - Update `RetrievalService` to perform a `JOIN` on `content_chunk_embeddings` where `provider = ?` instead of checking the legacy column.
3. **Data Population:** Execute a custom Artisan command.
   - The command queries `content_chunks` for any ID that does NOT have a corresponding `voyage` record in `content_chunk_embeddings`.
   - It extracts the `chunk_text` for these records.
   - It sends them to Voyage via `embedBatch` (passing `input_type=document`).
   - It inserts the new vector records.

## 6. Rollback Strategy
The migration is entirely non-destructive and heavily idempotent:
- **Resumability:** Because the script queries for chunks *missing* a `voyage` row in the new table, if it crashes at chunk 120/319, running it again will seamlessly pick up the remaining 199 chunks.
- **Data Safety:** The legacy `content_chunks.embedding` column (containing the Ollama vectors) is never modified or deleted.
- **Code Rollback:** Reverting `RetrievalService` to query the base table instantly restores the old behavior.

## 7. Exact Artisan Commands
To prepare the system:
```bash
php artisan make:migration create_content_chunk_embeddings_table
php artisan make:command MigrateVoyageEmbeddings
```
To execute the safe data migration:
```bash
php artisan rag:migrate-voyage
```
