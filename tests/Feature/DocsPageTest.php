<?php

namespace Tests\Feature;

use Tests\TestCase;

class DocsPageTest extends TestCase
{
    /**
     * Test halaman docs dapat diakses secara publik dan memuat seluruh bagian utama.
     */
    public function test_docs_page_is_accessible(): void
    {
        $response = $this->get('/docs');

        $response->assertStatus(200);
        $response->assertSee('School Class Progress');
        $response->assertSee('SCP Docs');
        $response->assertSee('5 Logika Bisnis Utama');
        $response->assertSee('Dokumentasi Operasional');
        $response->assertSee('Evaluasi Proyek');
        $response->assertSee('Matriks Ketercapaian Rubrik UKK');
    }

    /**
     * Test halaman docs dapat diakses dengan parameter section spesifik.
     */
    public function test_docs_page_supports_section_parameters(): void
    {
        $response = $this->get('/docs/operational-sop');

        $response->assertStatus(200);
        $response->assertSee('SOP 1: Tata Kelola Waka Kurikulum');
        $response->assertSee('SOP 2: Pelaksanaan KBM');
    }
}
