<?php
namespace Cadre\DomainSession\Storage;

use Cadre\DomainSession\Session;
use Cadre\DomainSession\SessionException;
use Cadre\DomainSession\SessionId;
use Cadre\DomainSession\SessionInterface;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

class FilesTest extends \PHPUnit_Framework_TestCase
{
    private $root;
    private $idFactory;

    public function setUp()
    {
        $this->root = vfsStream::setup('SessionDir');
    }

    public function testReadMissingId()
    {
        $id = SessionId::withNewValue();
        $session = Session::withId($id);

        $storage = new Files($this->root->url());

        $this->expectException(SessionException::class);

        $storage->read($id);
    }

    public function testReadUnserializableId()
    {
        $id = SessionId::withNewValue();
        $session = Session::withId($id);

        vfsStream::newFile(bin2hex($id))
            ->at($this->root)
            ->setContent('bogus-dsadh89h32huih3jk4h23');

        $storage = new Files($this->root->url());

        $this->expectException(SessionException::class);

        $storage->read($id);
    }

    public function testCreateAndWriteNewId()
    {
        $storage = new Files($this->root->url());

        $session = $storage->createNew();
        $id = $session->id()->value();

        $storage->write($session);

        $this->assertInstanceOf(SessionInterface::class, $storage->read($id));
        $this->assertEquals($session->id(), $storage->read($id)->id());
    }

    public function testWriteRegeneratedId()
    {
        $storage = new Files($this->root->url());

        $session = $storage->createNew();
        $id = $session->id()->value();

        $storage->write($session);

        $this->assertInstanceOf(SessionInterface::class, $storage->read($id));
        $this->assertEquals($session->id(), $storage->read($id)->id());

        $session->id()->regenerate();

        $storage->write($session);

        $this->expectException(SessionException::class);

        $storage->read($id);
    }

    public function testDeleteMissingId()
    {
        $id = SessionId::withNewValue();
        $session = Session::withId($id);

        $storage = new Files($this->root->url());

        $storage->write($session);

        $this->assertInstanceOf(SessionInterface::class, $storage->read($id));
        $this->assertEquals($session->id(), $storage->read($id)->id());

        $storage->delete($id);

        $this->expectException(SessionException::class);

        $s2 = $storage->read($id);
    }
}