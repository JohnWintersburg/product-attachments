<?php
namespace JohnWintersburg\ProductAttachments\Controller\Adminhtml\Attachment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as IoFile;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Psr\Log\LoggerInterface;

class Upload extends Action
{
    protected $resultJsonFactory;
    protected $filesystem;
    protected $file;
    protected $ioFile;
    protected $dateTime;
    protected $uploaderFactory;
    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Filesystem $filesystem,
        File $file,
        IoFile $ioFile,
        DateTime $dateTime,
        UploaderFactory $uploaderFactory,
        LoggerInterface $logger
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->ioFile = $ioFile;
        $this->dateTime = $dateTime;
        $this->uploaderFactory = $uploaderFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            if (isset($_FILES['product']['name']['attachment_file']) && $_FILES['product']['error']['attachment_file'] == UPLOAD_ERR_OK) {
                $uploader = $this->uploaderFactory->create(['fileId' => 'product[attachment_file]']);
                $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png']);
                $uploader->setAllowRenameFiles(true);
                $uploader->setFilesDispersion(false);

                $mediaDirectory = $this->filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                $result = $uploader->save($mediaDirectory->getAbsolutePath('johnwintersburg_productattachments'));

                if ($result['file']) {
                    return $result->setData(['success' => true, 'file' => $result['file']]);
                } else {
                    return $result->setData(['success' => false, 'error' => __('File could not be saved.')]);
                }
            } else {
                return $result->setData(['success' => false, 'error' => __('No file uploaded or upload error.')]);
            }
        } catch (\Exception $e) {
            $logger->critical($e);
            return $result->setData(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}
