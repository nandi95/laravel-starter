<template>
    <main>
        <header>
            <Link href="/">
                Home
            </Link>
            <Link href="/about">
                About Us
            </Link>
            <Link href="/become-a-host">
                Become a Host
            </Link>
            <Link href="/faq">
                FAQ
            </Link>
            <Link href="/blog">
                Blog
            </Link>
            <Link href="/contact">
                Contact
            </Link>
        </header>
        <slot />
    </main>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
</script>
