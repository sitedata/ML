<?php

namespace Rubix\ML\Extractors;

use Rubix\ML\Other\Tokenizers\Word;
use Rubix\ML\Other\Tokenizers\Tokenizer;
use InvalidArgumentException;
use RuntimeException;

class WordCountVectorizer implements Extractor
{
    /**
     * The maximum size of the vocabulary.
     *
     * @var int
     */
    protected $maxVocabulary;

    /**
     * An array of stop words i.e. words to filter out of the original text.
     *
     * @var array
     */
    protected $stopWords = [
        //
    ];

    /**
     * Should the text be normalized before tokenized? i.e. remove extra
     * whitespace and lowercase.
     *
     * @var bool
     */
    protected $normalize;

    /**
     * The tokenizer used to extract text data into tokenable values.
     *
     * @var \Rubix\ML\Other\Tokenizers\Tokenizer
     */
    protected $tokenizer;

    /**
     * The vocabulary of the fitted training set.
     *
     * @var array
     */
    protected $vocabulary = [
        //
    ];

    /**
     * @param  int  $maxVocabulary
     * @param  array  $stopWords
     * @param  bool  $normalize
     * @param  \Rubix\ML\Other\Tokenizers\Tokenizer  $tokenizer
     * @return void
     */
    public function __construct(int $maxVocabulary = PHP_INT_MAX, array $stopWords = [],
                                bool $normalize = true, Tokenizer $tokenizer = null)
    {
        if ($maxVocabulary < 1) {
            throw new InvalidArgumentException('The size of the vocabulary must'
                . ' be at least 1 word.');
        }

        foreach ($stopWords as $word) {
            if (!is_string($word)) {
                throw new InvalidArgumentException('Stop word must be a string,'
                    . gettype($word) . ' found.');
            }
        }

        if (is_null($tokenizer)) {
            $tokenizer = new Word();
        }

        if ($normalize === true) {
            $stopWords = array_map(function ($word) {
                return strtolower(trim($word));
            }, $stopWords);
        }

        $stopWords = array_flip(array_unique($stopWords));

        $this->maxVocabulary = $maxVocabulary;
        $this->stopWords = $stopWords;
        $this->normalize = $normalize;
        $this->tokenizer = $tokenizer;
    }

    /**
     * Return an array of words in the vocabulary.
     *
     * @return array
     */
    public function vocabulary() : array
    {
        return array_flip($this->vocabulary);
    }

    /**
     * Build the vocabulary for the vectorizer.
     *
     * @param  array  $samples
     * @return void
     */
    public function fit(array $samples) : void
    {
        $this->vocabulary = $frequencies = [];

        foreach ($samples as $sample) {
            if ($this->normalize) {
                $sample = preg_replace('/\s+/', ' ', trim(strtolower($sample)));
            }

            foreach ($this->tokenizer->tokenize($sample) as $token) {
                if (!isset($this->stopWords[$token])) {
                    if (isset($frequencies[$token])) {
                        $frequencies[$token]++;
                    } else {
                        $frequencies[$token] = 1;
                    }
                }
            }
        }

        if (count($frequencies) > $this->maxVocabulary) {
            arsort($frequencies);

            $frequencies = array_slice($frequencies, 0, $this->maxVocabulary);
        }

        $index = 0;

        foreach ($frequencies as $token => $count) {
            $this->vocabulary[$token] = $index++;
        }
    }

    /**
     * Transform the text dataset into a collection of vectors where the value
     * is equal to the number of times that word appears in the sample.
     *
     * @param  array  $samples
     * @throws \RuntimeException
     * @return array
     */
    public function extract(array $samples) : array
    {
        if (empty($this->vocabulary)) {
            throw new RuntimeException('Vocabulary is empty, try fitting a'
                . ' dataset first.');
        }

        $vectors = [];

        foreach ($samples as $sample) {
            if (is_string($sample)) {
                $vectors[] = $this->vectorize($sample);
            }
        }

        return $vectors;
    }

    /**
     * Convert a string into a vector where the scalars are token frequencies.
     *
     * @param  string  $sample
     * @return array
     */
    public function vectorize(string $sample) : array
    {
        $vector = array_fill(0, count($this->vocabulary), 0);

        if ($this->normalize) {
            $sample = preg_replace('/\s+/', ' ', trim(strtolower($sample)));
        }

        foreach ($this->tokenizer->tokenize($sample) as $token) {
            if (isset($this->vocabulary[$token])) {
                $vector[$this->vocabulary[$token]]++;
            }
        }

        return $vector;
    }
}