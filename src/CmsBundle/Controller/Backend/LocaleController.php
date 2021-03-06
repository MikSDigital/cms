<?php

namespace Opifer\CmsBundle\Controller\Backend;

use APY\DataGridBundle\Grid\Action\RowAction;
use APY\DataGridBundle\Grid\Source\Entity;
use Opifer\CmsBundle\Entity\Locale;
use Opifer\CmsBundle\Form\Type\LocaleType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleController extends Controller
{

    /**
     * @return Response
     */
    public function indexAction()
    {
        $source = new Entity(Locale::class);

        $editAction = new RowAction('button.edit', 'opifer_cms_locale_edit');
        $editAction->setRouteParameters(['id']);

        /* @var $grid \APY\DataGridBundle\Grid\Grid */
        $grid = $this->get('grid');
        $grid->setId('locale')
            ->setSource($source)
            ->addRowAction($editAction)
          ;


        return $grid->getGridResponse('OpiferCmsBundle:Backend/Locale:index.html.twig');
    }

    /**
     * Create an event
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function createAction(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $locale = new Locale();

        $form = $this->createForm(new LocaleType(), $locale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($locale);
            $em->flush();

            $this->addFlash('success', 'Locale has been created successfully');

            return $this->redirectToRoute('opifer_cms_locale_edit', ['id' => $locale->getId()]);
        }

        return $this->render('OpiferCmsBundle:Backend/Locale:create.html.twig', [
            'locale' => $locale,
            'form' => $form->createView()
        ]);
    }

    /**
     *
     *
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, $id = null)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();

        if (is_numeric($id)) {
            $locale = $em->getRepository('OpiferCmsBundle:Locale')->find($id);
        } else {
            $locale = $em->getRepository('OpiferCmsBundle:Locale')->findOneByLocale($id);
        }

        $form = $this->createForm(new LocaleType(), $locale);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($locale);
            $em->flush();

            $this->addFlash('success', 'Locale has been updated successfully');

            return $this->redirectToRoute('opifer_cms_locale_edit', ['id' => $locale->getId()]);
        }

        return $this->render('OpiferCmsBundle:Backend/Locale:edit.html.twig', [
            'locale' => $locale,
            'form' => $form->createView()
        ]);
    }
}
