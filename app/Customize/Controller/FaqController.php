<?php

namespace Customize\Controller;

use Customize\Repository\FaqCategoryRepository;
use Customize\Repository\FaqRepository;
use Eccube\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

class FaqController extends AbstractController
{
    /**
     * @var FaqRepository
     */
    protected $faqRepository;

    /**
     * @var FaqCategoryRepository
     */
    protected $faqCategoryRepository;

    public function __construct(
        FaqRepository $faqRepository,
        FaqCategoryRepository $faqCategoryRepository
    ) {
        $this->faqRepository = $faqRepository;
        $this->faqCategoryRepository = $faqCategoryRepository;
    }

    /**
     * @Method("GET")
     * @Route("/faq", name="faq")
     * @Template("Faq/index.twig")
     */
    public function index(Request $request)
    {
        $searchData = $request->query->all();
        $Faqs = $this->faqRepository->getQueryBuilderBySearchData($searchData)
            ->getQuery()
            ->getResult();
        $FaqCategories = $this->faqCategoryRepository->getQueryBuilderBySearchData($searchData)
            ->getQuery()
            ->getResult();

        return [
            'Faqs' => $Faqs,
            'FaqCategories' => $FaqCategories,
            'FaqCategoriesForLink' => $this->faqCategoryRepository->findAll(),
        ];
    }
}