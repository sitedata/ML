<?php

namespace Rubix\ML\NeuralNet\Layers;

use Tensor\Matrix;
use Rubix\ML\Deferred;
use Rubix\ML\NeuralNet\Optimizers\Optimizer;
use Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction;
use Rubix\ML\Exceptions\RuntimeException;

/**
 * Activation
 *
 * Activation layers apply a user-defined non-linear activation function to their
 * inputs.
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Andrew DalPino
 */
class Activation implements Hidden
{
    /**
     * The function that computes the output of the layer.
     *
     * @var \Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction
     */
    protected \Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction $activationFn;

    /**
     * The width of the layer.
     *
     * @var int<0,max>|null
     */
    protected ?int $width = null;

    /**
     * The memorized input matrix.
     *
     * @var \Tensor\Matrix|null
     */
    protected ?\Tensor\Matrix $input = null;

    /**
     * The memorized activation matrix.
     *
     * @var \Tensor\Matrix|null
     */
    protected ?\Tensor\Matrix $computed = null;

    /**
     * @param \Rubix\ML\NeuralNet\ActivationFunctions\ActivationFunction $activationFn
     */
    public function __construct(ActivationFunction $activationFn)
    {
        $this->activationFn = $activationFn;
    }

    /**
     * Return the width of the layer.
     *
     * @internal
     *
     * @throws \Rubix\ML\Exceptions\RuntimeException
     * @return int<0,max>
     */
    public function width() : int
    {
        if ($this->width === null) {
            throw new RuntimeException('Layer has not been initialized.');
        }

        return $this->width;
    }

    /**
     * Initialize the layer with the fan in from the previous layer and return
     * the fan out for this layer.
     *
     * @internal
     *
     * @param int<0,max> $fanIn
     * @return int<0,max>
     */
    public function initialize(int $fanIn) : int
    {
        $fanOut = $fanIn;

        $this->width = $fanOut;

        return $fanOut;
    }

    /**
     * Compute a forward pass through the layer.
     *
     * @internal
     *
     * @param \Tensor\Matrix $input
     * @return \Tensor\Matrix
     */
    public function forward(Matrix $input) : Matrix
    {
        $computed = $this->activationFn->compute($input);

        $this->input = $input;
        $this->computed = $computed;

        return $computed;
    }

    /**
     * Compute an inferential pass through the layer.
     *
     * @internal
     *
     * @param \Tensor\Matrix $input
     * @return \Tensor\Matrix
     */
    public function infer(Matrix $input) : Matrix
    {
        return $this->activationFn->compute($input);
    }

    /**
     * Calculate the gradient and update the parameters of the layer.
     *
     * @internal
     *
     * @param \Rubix\ML\Deferred $prevGradient
     * @param \Rubix\ML\NeuralNet\Optimizers\Optimizer $optimizer
     * @throws \Rubix\ML\Exceptions\RuntimeException
     * @return \Rubix\ML\Deferred
     */
    public function back(Deferred $prevGradient, Optimizer $optimizer) : Deferred
    {
        if (!$this->input or !$this->computed) {
            throw new RuntimeException('Must perform forward pass before'
                . ' backpropagating.');
        }

        $input = $this->input;
        $computed = $this->computed;

        $this->input = $this->computed = null;

        return new Deferred(
            [$this, 'gradient'],
            [$input, $computed, $prevGradient]
        );
    }

    /**
     * Calculate the gradient for the previous layer.
     *
     * @internal
     *
     * @param \Tensor\Matrix $input
     * @param \Tensor\Matrix $computed
     * @param \Rubix\ML\Deferred $prevGradient
     * @return \Tensor\Matrix
     */
    public function gradient(Matrix $input, Matrix $computed, Deferred $prevGradient) : Matrix
    {
        return $this->activationFn->differentiate($input, $computed)
            ->multiply($prevGradient());
    }

    /**
     * Return the string representation of the object.
     *
     * @internal
     *
     * @return string
     */
    public function __toString() : string
    {
        return "Activation (activation fn: {$this->activationFn})";
    }
}
