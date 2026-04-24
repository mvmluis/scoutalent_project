<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Country; // se vais guardar no BD

class Countries extends Controller
{
    protected $base = 'https://v3.football.api-sports.io';

    protected function headers()
    {
        return [
            'x-apisports-key' => config('services.football.key', env('FOOTBALL_API_KEY')),
        ];
    }

    // chama API e mostra view (index)
    public function index()
    {
        $res = Http::withHeaders($this->headers())
            ->acceptJson()
            ->get($this->base . '/countries');

        if (! $res->successful()) {
            Log::warning('Football API /countries failed: '.$res->status().' '.$res->body());
            $countries = [];
            $error = 'Erro ao consultar API ('.$res->status().')';
        } else {
            $body = $res->json();
            $countries = collect($body['response'] ?? [])->map(function ($c) {
                return [
                    'id'        => $c['code'] ?? uniqid(), // se não tiver id na API
                    'name'      => $c['name'] ?? $c['country'] ?? '—',
                    'code'      => $c['code'] ?? null,
                    'continent' => $c['continent'] ?? null,
                    'flag'      => $c['flag'] ?? null,
                ];
            });
            $error = null;
        }

        return view('adminCountries.layout.dashboard', compact('countries','error'));
    }

    /**
     * Sincronizar e gravar no BD.
     */
    public function edit($id)
    {
        $country = Country::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        // garante que a variável existe na view
        return view('adminCountries.edit', compact('country'));
    }

    /**
     * Update
     */
    public function update(Request $request, $id)
    {
        $country = Country::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:8',
            'continent' => 'nullable|string|max:80',
            'flag' => 'nullable|url',
        ]);

        $country->update($data);

        return redirect()
            ->route('admin.profile.country.edit', $country->id)
            ->with('success', 'País atualizado com sucesso.');
    }

    /**
     * Destroy
     */
    public function destroy($id)
    {
        $country = Country::where('id', $id)
            ->orWhere('code', $id)
            ->firstOrFail();

        $country->delete();

        return redirect()->route('profile.country')->with('success', 'País eliminado.');
    }

    /**
     * Sync (chama API e guarda/actualiza no BD)
     */
    public function sync(Request $request)
    {
        try {
            $res = Http::withHeaders($this->headers())->acceptJson()->get($this->base . '/countries');

            if (! $res->successful()) {
                return back()->with('error', 'Erro ao sincronizar: ' . $res->status());
            }

            $list = $res->json('response', []);
            $created = 0;
            $updated = 0;
            $skipped = 0;

            DB::transaction(function () use ($list, &$created, &$updated, &$skipped) {
                foreach ($list as $c) {
                    // normalizações simples
                    $code = isset($c['code']) && $c['code'] !== '' ? strtoupper($c['code']) : null;
                    $name = $c['name'] ?? ($c['country'] ?? null);
                    $continent = $c['continent'] ?? null;
                    $flag = $c['flag'] ?? null;

                    // procura preferencial por code, fallback por nome (se code null)
                    if ($code) {
                        $country = Country::firstOrNew(['code' => $code]);
                    } else {
                        // quando não há code tenta achar por name exacto
                        $country = Country::firstOrNew(['name' => $name]);
                    }

                    // Se é novo -> preenche tudo
                    if (! $country->exists) {
                        $country->fill([
                            'code' => $code,
                            'name' => $name ?? '—',
                            'continent' => $continent,
                            'flag' => $flag,
                            // guarda meta como JSON (certifica que a coluna é json/text)
                            'meta' => $c,
                        ])->save();

                        $created++;
                        continue;
                    }

                    // Se já existe -> regras:
                    //  - se tiver manually_edited_at definido, saltar (opcional) — descomentem se quiserem esta proteção
                    // if ($country->manually_edited_at) { $skipped++; continue; }

                    //  - só atualizar campos vazios (preservando edições manuais)
                    $dirty = false;

                    if (empty($country->name) && $name) {
                        $country->name = $name;
                        $dirty = true;
                    }

                    if (empty($country->continent) && $continent) {
                        $country->continent = $continent;
                        $dirty = true;
                    }

                    if (empty($country->flag) && $flag) {
                        $country->flag = $flag;
                        $dirty = true;
                    }

                    // Merge de meta: mantém chaves antigas e actualiza com novas da API
                    $existingMeta = is_string($country->meta) ? json_decode($country->meta, true) : ($country->meta ?? []);
                    if (!is_array($existingMeta)) $existingMeta = [];
                    $mergedMeta = array_merge($existingMeta, $c);
                    $country->meta = $mergedMeta;
                    $dirty = true; // forçamos save para garantir meta actualizada

                    if ($dirty) {
                        $country->save();
                        $updated++;
                    } else {
                        $skipped++;
                    }
                }
            });

            $msg = "Sincronização completa — criados: {$created}, actualizados: {$updated}, ignorados: {$skipped}.";
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('Countries sync error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Erro ao sincronizar: ' . $e->getMessage());
        }
    }
}
