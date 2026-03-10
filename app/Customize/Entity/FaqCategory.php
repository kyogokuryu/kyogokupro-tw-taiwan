<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\FaqCategory')) {
    /**
     * FaqCategory
     *
     * @ORM\Table(name="dtb_faq_category")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\FaqCategoryRepository")
     */
    class FaqCategory extends \Eccube\Entity\AbstractEntity
    {
        /**
         * @var integer
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var string
         *
         * @ORM\Column(name="name", type="string")
         */
        private $name;

        /**
         * @var string
         *
         * @ORM\Column(name="icon_name", type="string", nullable=true)
         */
        private $icon_name;

        /**
         * @var \Doctrine\Common\Collections\Collection|Faq[]
         *
         * @ORM\OneToMany(targetEntity="Customize\Entity\Faq", mappedBy="FaqCategory", cascade={"persist","remove"})
         */
        private $Faqs;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="update_date", type="datetimetz")
         */
        private $update_date;

        /**
         * Get id.
         *
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * Set name.
         *
         * @param string $name
         *
         * @return FaqCategory
         */
        public function setName($name)
        {
            $this->name = $name;

            return $this;
        }

        /**
         * Get name.
         *
         * @return string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * Set icon_name.
         *
         * @param string $iconName
         *
         * @return FaqCategory
         */
        public function setIconName($iconName)
        {
            $this->icon_name = $iconName;

            return $this;
        }

        /**
         * Get icon_name.
         *
         * @return string
         */
        public function getIconName()
        {
            return $this->icon_name;
        }

        /**
         * Add faq.
         *
         * @param Faq $Faq
         *
         * @return Order
         */
        public function addFaq(Faq $Faq)
        {
            $this->Faqs[] = $Faq;

            return $this;
        }

        /**
         * Remove faq.
         *
         * @param Faq $Faq
         *
         * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
         */
        public function removeFaq(Faq $Faq)
        {
            return $this->Faqs->removeElement($Faq);
        }

        /**
         * Get faqs.
         *
         * @return \Doctrine\Common\Collections\Collection|Faq[]
         */
        public function getFaqs()
        {
            return $this->Faqs;
        }


        /**
         * Set create_date.
         *
         * @param \DateTime $createDate
         *
         * @return FaqCategory
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;

            return $this;
        }

        /**
         * Get create_date.
         *
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * Set update_date.
         *
         * @param \DateTime $updateDate
         *
         * @return Faq
         */
        public function setUpdateDate($updateDate)
        {
            $this->update_date = $updateDate;

            return $this;
        }

        /**
         * Get update_date.
         *
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }
    }
}