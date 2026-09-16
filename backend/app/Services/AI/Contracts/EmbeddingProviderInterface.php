<?php

namespace App\Services\AI\Contracts;

use App\Exceptions\AiProviderException;

/**
 * Provider-agnostic embedding contract (Req 4.2, 8.1).
 * Swap Voyage ↔ OpenAI by changing AI_EMBEDDING_PROVIDER in .env.
 */
interface EmbeddingProviderInterface
{
    /**
     * Embed a single text string.
     *
     * @return float[] Dense vector of floats with length = configured dimension
     *
     * @throws AiProviderException on permanent failure
     */
    public function embed(string $text, ?string $inputType = null): array;

    /**
     * Embed multiple texts in one API call (batched).
     *
     * @param  string[]  $texts
     * @return float[][] Array of vectors, indexed the same as $texts
     *
     * @throws AiProviderException on permanent failure
     */
    public function embedBatch(array $texts, ?string $inputType = null): array;

    /**
     * The dimension of vectors produced by this provider.
     */
    public function getDimension(): int;
}
