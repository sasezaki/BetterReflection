<?php

function returnsInt(): int
{
}

function returnsString(): string
{
}

function returnsObject(): \stdClass
{
}

function returnsNull(): null
{
}

function returnsNothing()
{
}

function returnsVoid(): void
{
}

function returnsNullableInt(): ?int
{
}

function returnsNullableString(): ?string
{
}

function returnsNullableObject(): ?\stdClass
{
}

function returnsUnion(): int|string
{
}

function returnsIntersection(): DateTime&DateTimeImmutable
{
}
