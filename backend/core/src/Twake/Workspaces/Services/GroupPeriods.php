<?php

namespace Twake\Workspaces\Services;

use Twake\Workspaces\Entity\AppPricingInstance;
use Twake\Workspaces\Entity\ClosedGroupPeriod;
use Twake\Workspaces\Entity\GroupManager;
use Twake\Workspaces\Entity\GroupPeriod;
use Twake\Workspaces\Entity\GroupPricingInstance;
use Twake\Workspaces\Model\GroupPeriodInterface;
use App\App;
class GroupPeriods
{

    private $doctrine;

    public function __construct(App $app)
    {
        $this->doctrine = $app->getServices()->get("app.twake_doctrine");
    }

    public function changePlanOrRenew($group, $billingType, $planId)
    {

        $groupPricingInstanceRepository = $this->doctrine->getRepository("Twake\Workspaces:GroupPricingInstance");
        $grouppricinginstance = $groupPricingInstanceRepository->findOneBy(Array("group" => $group));

        $groupPeriodRepository = $this->doctrine->getRepository("Twake\Workspaces:GroupPeriod");
        $groupPeriod = $groupPeriodRepository->findOneBy(Array("group" => $group));

        $groupUserRepository = $this->doctrine->getRepository("Twake\Workspaces:GroupUser");
        $groupUsers = $groupUserRepository->findBy(Array("group" => $group));

        $appPricingRepository = $this->doctrine->getRepository("Twake\Workspaces:AppPricingInstance");
        $appPricings = $appPricingRepository->findBy(Array("group" => $group));

        $groupAppRepository = $this->doctrine->getRepository("Twake\Workspaces:GroupApp");
        $groupApps = $groupAppRepository->findBy(Array("group" => $group));

        $pricingRepository = $this->doctrine->getRepository("Twake\Workspaces:PricingPlan");
        $pricing = $pricingRepository->findOneBy(Array("id" => $planId));

        if (!$groupPeriod) {
            return false;
        } else {

            $closedGroupPeriod = new ClosedGroupPeriod($groupPeriod);

            foreach ($groupApps as $groupApp) {
                $newAppPricing = new AppPricingInstance($groupApp);
                $this->doctrine->persist($newAppPricing);
            }
            $newGroupPricing = new GroupPricingInstance($group, $billingType, $pricing);
            $date = new \DateTime();

            if ($billingType == "monthly") {
                $date->modify('+1 month');
            }
            if ($billingType == "yearly") {
                $date->modify('+1 year');
            }

            $newGroupPricing->setEndAt($date);
            $newGroupPeriod = new GroupPeriod($group);
            $newGroupPeriod->setExpectedCost($groupPeriod->getExpectedCost());
            $newGroupPeriod->setGroupPricingInstance($newGroupPricing);

            if ($grouppricinginstance) {
                $this->doctrine->remove($grouppricinginstance);

                foreach ($appPricings as $appPricing) {
                    $this->doctrine->remove($appPricing);
                }
            }
            $this->doctrine->remove($groupPeriod);

            //TODO CALCUL COUT ET FAIRE PAYER (UN JOUR P-Ê)
            // Si payement OK
            $closedGroupPeriod->setBilled(true);
            // Sinon
            // $closedGroupPeriod->setBilled(false);

            //Reset user period utilisation
            foreach ($groupUsers as $groupuser) {//User left group between two periods, it can be removed
                if ($groupuser->getNbWorkspace() == 0) {
                    $this->doctrine->remove($groupuser);
                } else {
                    $groupuser->setConnectionsPeriod(0);
                    $groupuser->setUsedAppsToday(Array());
                    $this->doctrine->persist($groupuser);
                }
            }

            $this->doctrine->persist($closedGroupPeriod);
            $this->doctrine->persist($newGroupPricing);
            $this->doctrine->persist($newGroupPeriod);
            $this->doctrine->flush();
            return true;
        }


    }

    public function groupPeriodOverCost($groupPeriod)
    {

        $closedGroupPeriod = new ClosedGroupPeriod($groupPeriod);

        $newGroupPeriod = new GroupPeriod($groupPeriod->getGroup());
        $newGroupPeriod->setExpectedCost($groupPeriod->getExpectedCost());
        $date = new \DateTime();
        $date->modify('+1 day');
        $newGroupPeriod->setPeriodStartedAt($date);

        $newGroupPeriod->setPeriodExpectedToEndAt($groupPeriod->getPeriodExpectedToEndAt());
        $newGroupPeriod->setGroupPricingInstance($groupPeriod->getGroupPricingInstance());

        //TODO FAIRE PAYER
        // Si payement OK
        $closedGroupPeriod->setBilled(true);
        // Sinon
        // $closedGroupPeriod->setBilled(false);

        $this->doctrine->remove($groupPeriod);
        $this->doctrine->persist($closedGroupPeriod);
        $this->doctrine->persist($newGroupPeriod);
        $this->doctrine->flush();
    }


    public function endGroupPricing($groupPeriod)
    {

        $group = $groupPeriod->getGroup();
        $group->setPricingPlan(null);

        $appPricingRepository = $this->doctrine->getRepository("Twake\Workspaces:AppPricingInstance");
        $appPricings = $appPricingRepository->findBy(Array("group" => $group));

        $groupPricing = $groupPeriod->getGroupPricingInstance();
        $groupPeriod->setGroupPricingInstance(null);
        $groupPeriod->setCurrentCost(0);

        $closedGroupPeriod = new ClosedGroupPeriod($groupPeriod);
        $closedGroupPeriod->setBilled(false);

        foreach ($appPricings as $appPricing) {
            $this->doctrine->remove($appPricing);
        }


        $this->doctrine->persist($groupPeriod);
        $this->doctrine->persist($group);
        $this->doctrine->persist($closedGroupPeriod);
        $this->doctrine->remove($groupPricing);
        $this->doctrine->flush();
    }

    public function init($group, $pricing_plan)
    {
        $groupPeriodRepository = $this->doctrine->getRepository("Twake\Workspaces:GroupPeriod");

        $groupPeriod = $groupPeriodRepository->findOneBy(Array("group" => $group));

        if ($groupPeriod) {
            return false;
        } else {
            $groupPricing = new GroupPricingInstance($group, "monthly", $pricing_plan);
            $date = new \DateTime();
            $date->modify('+1 month');
            $groupPricing->setEndAt($date);
            $groupPeriod = new GroupPeriod($group, $groupPricing);
            $groupPeriod->setGroupPricingInstance($groupPricing);

            $this->doctrine->persist($groupPricing);
            $this->doctrine->persist($groupPeriod);
            $this->doctrine->flush();
            return true;
        }
    }
}