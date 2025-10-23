<?php

namespace App\Model;

class Outing
{
    private int $id;
    private string $title;
    private ?string $description;
    private ?string $meeting_point;
    private string $event_date;
    private string $event_time;
    private ?float $estimated_amount;
    private string $created_by;
    private string $status;

    // Constructor vacío con valores por defecto

    public function __construct()
    {
        $this->id = 0;
        $this->title = '';
        $this->description = null;
        $this->meeting_point = null;
        $this->event_date = '';
        $this->event_time = '';
        $this->estimated_amount = null;
        $this->created_by = '';
        $this->status = 'pending';
    }


    // Getters y Setters
    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getMeetingPoint(): ?string
    {
        return $this->meeting_point;
    }

    public function setMeetingPoint(?string $meeting_point): void
    {
        $this->meeting_point = $meeting_point;
    }

    public function getEventDate(): string
    {
        return $this->event_date;
    }

    public function setEventDate(string $event_date): void
    {
        $this->event_date = $event_date;
    }

    public function getEventTime(): string
    {
        return $this->event_time;
    }

    public function setEventTime(string $event_time): void
    {
        $this->event_time = $event_time;
    }

    public function getEstimatedAmount(): ?float
    {
        return $this->estimated_amount;
    }

    public function setEstimatedAmount(?float $estimated_amount): void
    {
        $this->estimated_amount = $estimated_amount;
    }

    public function getCreatedBy(): string
    {
        return $this->created_by;
    }

    public function setCreatedBy(string $created_by): void
    {
        $this->created_by = $created_by;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }


    // Método para guardar el outing en la base de datos
    public function save(): bool
    {
        $result = true;
        return $result;
    }

    // Método estático para obtener un outing por su ID
    public static function getById(int $id) {}

    // Método estático para obtener todos los outings
    public static function getAll(): array
    {
        $outings = [];
        return $outings;
    }
}
