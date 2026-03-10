<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Customize\Entity\Faq')) {
    /**
     * Faq
     *
     * @ORM\Table(name="dtb_faq")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Customize\Repository\FaqRepository")
     */
    class Faq extends \Eccube\Entity\AbstractEntity
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
         * @var \Customize\Entity\FaqCategory
         *
         * @ORM\ManyToOne(targetEntity="Customize\Entity\FaqCategory", inversedBy="Faqs")
         * @ORM\JoinColumns({
         *  @ORM\JoinColumn(name="faq_category_id", referencedColumnName="id")
         * })
         */
        private $FaqCategory;

        /**
         * @var string
         *
         * @ORM\Column(name="question", type="string")
         */
        private $question;

        /**
         * @var string
         *
         * @ORM\Column(name="answer", type="text")
         */
        private $answer;

        /**
         * @var boolean
         *
         * @ORM\Column(name="display_top", type="boolean", options={"default":false})
         */
        private $display_top;

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
         * Set faqCategory.
         *
         * @param FaqCategory|null $faqCategory
         *
         * @return Faq
         */
        public function setFaqCategory(FaqCategory $faqCategory = null)
        {
            $this->FaqCategory = $faqCategory;

            return $this;
        }

        /**
         * Get faqCategory.
         *
         * @return FaqCategory|null
         */
        public function getFaqCategory()
        {
            return $this->FaqCategory;
        }

        /**
         * Set question.
         *
         * @param string $question
         *
         * @return Faq
         */
        public function setQuestion($question)
        {
            $this->question = $question;

            return $this;
        }

        /**
         * Get question.
         *
         * @return string
         */
        public function getQuestion()
        {
            return $this->question;
        }

        /**
         * Set answer.
         *
         * @param string $answer
         *
         * @return Faq
         */
        public function setAnswer($answer)
        {
            $this->answer = $answer;

            return $this;
        }

        /**
         * Get answer.
         *
         * @return string
         */
        public function getAnswer()
        {
            return $this->answer;
        }

        /**
         * Set display_top.
         *
         * @param bool $display_top
         *
         * @return Faq
         */
        public function setDisplayTop($display_top = false)
        {
            $this->display_top = $display_top;

            return $this;
        }

        /**
         * Get display_top.
         *
         * @return boolean
         */
        public function isDisplayTop()
        {
            return $this->display_top;
        }

        /**
         * Set create_date.
         *
         * @param \DateTime $createDate
         *
         * @return Faq
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