<?php
/**
 * Copyright(c) 2019 SYSTEM_KD
 * Date: 2019/08/07
 */

namespace Plugin\PinpointSale\Form\Transformer;


use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class CustomDateTimeTransformer implements DataTransformerInterface
{

    /**
     * Transforms a value from the original representation to a transformed representation.
     *
     * This method is called on two occasions inside a form field:
     *
     * 1. When the form field is initialized with the data attached from the datasource (object or array).
     * 2. When data from a request is submitted using {@link Form::submit()} to transform the new input data
     *    back into the renderable format. For example if you have a date field and submit '2009-10-10'
     *    you might accept this value because its easily parsed, but the transformer still writes back
     *    "2009/10/10" onto the form field (for further displaying or other purposes).
     *
     * This method must be able to deal with empty values. Usually this will
     * be NULL, but depending on your implementation other empty values are
     * possible as well (such as empty strings). The reasoning behind this is
     * that value transformers must be chainable. If the transform() method
     * of the first value transformer outputs NULL, the second value transformer
     * must be able to process that value.
     *
     * By convention, transform() should return an empty string if NULL is
     * passed.
     *
     * @param mixed $value The value in the original representation
     *
     * @return mixed The value in the transformed representation
     *
     * @throws TransformationFailedException when the transformation fails
     * @throws \Exception
     */
    public function transform($value)
    {
        if (is_null($value)) {
            return null;
        }

        if (!isset($value['custom_date'])
            || !isset($value['custom_time'])) {
            return null;
        }

        if (is_null($value['custom_date'])
            || is_null($value['custom_time'])) {
            return null;
        }

        /** @var \DateTime $customDate */
        $customDate = $value['custom_date'];

        /** @var \DateTime $customTime */
        $customTime = $value['custom_time'];

        $resultDateTime = new \DateTime();
        $resultDateTime->setDate(
            $customDate->format('Y'),
            $customDate->format('m'),
            $customDate->format('d')
        );

        $resultDateTime->setTime(
            $customTime->format('H'),
            $customTime->format('i')
        );

        return $resultDateTime;
    }

    /**
     * Transforms a value from the transformed representation to its original
     * representation.
     *
     * This method is called when {@link Form::submit()} is called to transform the requests tainted data
     * into an acceptable format for your data processing/model layer.
     *
     * This method must be able to deal with empty values. Usually this will
     * be an empty string, but depending on your implementation other empty
     * values are possible as well (such as NULL). The reasoning behind
     * this is that value transformers must be chainable. If the
     * reverseTransform() method of the first value transformer outputs an
     * empty string, the second value transformer must be able to process that
     * value.
     *
     * By convention, reverseTransform() should return NULL if an empty string
     * is passed.
     *
     * @param mixed $value The value in the transformed representation
     *
     * @return mixed The value in the original representation
     *
     * @throws TransformationFailedException when the transformation fails
     */
    public function reverseTransform($value)
    {
        if (is_null($value)) {
            return $value;
        }

        // Formへセット
        $resultDateTime['custom_date'] = $value;
        $resultDateTime['custom_time'] = $value;

        return $resultDateTime;
    }
}
