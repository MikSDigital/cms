<?php

namespace Opifer\EavBundle\ValueProvider;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;

use Opifer\EavBundle\Form\Transformer\CollectionToObjectTransformer;

class SelectValueProvider extends AbstractValueProvider implements ValueProviderInterface
{
    /** @var string */
    protected $optionClass;

    /**
     * Constructor
     *
     * @param string $optionClass
     */
    public function __construct($optionClass)
    {
        $this->optionClass = $optionClass;
    }

    /**
     * Select form
     *
     * Generates a select input that holds all attribute options
     *
     * This valuetype needs the CollectionToObjectTransformer, so we can set a single
     * relation on a one-to-many relation type.
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $attributeId = $options['attribute']->getId();
        $transformer = new CollectionToObjectTransformer();

        $builder->add(
            $builder->create('options', 'entity', [
                'label'         => $options['attribute']->getDisplayName(),
                'multiple'      => false,
                'property'      => 'displayName',
                'class'         => $this->optionClass,
                'query_builder' => function (EntityRepository $optionRepository) use ($attributeId) {
                    return $optionRepository->createQueryBuilder('o')
                        ->add('orderBy', 'o.sort ASC')
                        ->innerJoin('o.attribute', 'a')
                        ->where('a.id = :attributeId')
                        ->setParameter('attributeId', $attributeId);
                }
            ])->addModelTransformer($transformer)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getEntity()
    {
        return 'Opifer\EavBundle\Entity\SelectValue';
    }
}
