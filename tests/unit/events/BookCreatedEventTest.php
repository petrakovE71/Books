<?php

declare(strict_types=1);

namespace tests\unit\events;

use app\common\events\BookCreatedEvent;
use app\models\Book;
use Codeception\Test\Unit;

class BookCreatedEventTest extends Unit
{
    public function testConstructor(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $event = new BookCreatedEvent($book);

        $this->assertInstanceOf(BookCreatedEvent::class, $event);
        $this->assertEquals($book, $event->book);
    }

    public function testEventNameConstant(): void
    {
        $this->assertEquals('bookCreated', BookCreatedEvent::EVENT_NAME);
    }

    public function testBookProperty(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $event = new BookCreatedEvent($book);

        $this->assertSame($book, $event->book);
        $this->assertEquals(1, $event->book->id);
        $this->assertEquals('Test Book', $event->book->title);
    }

    public function testReadonlyProperty(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $event = new BookCreatedEvent($book);

        $this->expectException(\Error::class);
        $event->book = new Book();
    }

    public function testEventCanBeSerializedForQueue(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $event = new BookCreatedEvent($book);

        $serialized = serialize($event);
        $this->assertIsString($serialized);

        $unserialized = unserialize($serialized);
        $this->assertInstanceOf(BookCreatedEvent::class, $unserialized);
    }

    public function testGetBookId(): void
    {
        $book = new Book([
            'id' => 123,
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $event = new BookCreatedEvent($book);

        $this->assertEquals(123, $event->getBookId());
    }

    public function testGetBookTitle(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Event Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $event = new BookCreatedEvent($book);

        $this->assertEquals('Event Test Book', $event->getBookTitle());
    }

    public function testGetAuthorIds(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        // Mock the getAuthorIds method
        $book->expects($this->any())
            ->method('getAuthorIds')
            ->willReturn([1, 2, 3]);

        $event = new BookCreatedEvent($book);

        $authorIds = $event->getAuthorIds();

        $this->assertIsArray($authorIds);
        $this->assertEquals([1, 2, 3], $authorIds);
    }

    public function testToArray(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $event = new BookCreatedEvent($book);

        $array = $event->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('book_id', $array);
        $this->assertArrayHasKey('title', $array);
        $this->assertArrayHasKey('year', $array);
        $this->assertArrayHasKey('isbn', $array);
    }

    public function testEventTimestamp(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Test Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $beforeTime = time();
        $event = new BookCreatedEvent($book);
        $afterTime = time();

        $eventTime = $event->getTimestamp();

        $this->assertGreaterThanOrEqual($beforeTime, $eventTime);
        $this->assertLessThanOrEqual($afterTime, $eventTime);
    }

    public function testEventWithMultipleAuthors(): void
    {
        $book = new Book([
            'id' => 1,
            'title' => 'Multi-Author Book',
            'year' => 2024,
            'isbn' => '978-1-111-11111-1',
        ]);

        $event = new BookCreatedEvent($book);

        $this->assertInstanceOf(BookCreatedEvent::class, $event);
        $this->assertEquals('Multi-Author Book', $event->book->title);
    }
}
