<?php

namespace HelloWorld\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\Plugins\PluginManager;
use HelloWorld\Models\Greeting;
use Illuminate\Http\Request;

class HelloController extends Controller
{
    /**
     * Display the plugin's main page.
     */
    public function index()
    {
        $pluginManager = app(PluginManager::class);
        $plugin = $pluginManager->find('hello-world');
        $instance = $pluginManager->getLoadedPlugin('hello-world');
        
        $greeting = $instance?->getGreeting() ?? 'Hello, World!';
        $greetingsCount = Greeting::count();

        return view('hello-world::index', [
            'greeting' => $greeting,
            'greetingsCount' => $greetingsCount,
            'plugin' => $plugin,
        ]);
    }

    /**
     * Display list of greetings.
     */
    public function greetings()
    {
        $greetings = Greeting::latest()->paginate(10);

        return view('hello-world::greetings', [
            'greetings' => $greetings,
        ]);
    }

    /**
     * Store a new greeting.
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:255',
            'author' => 'nullable|string|max:100',
        ]);

        Greeting::create([
            'message' => $request->input('message'),
            'author' => $request->input('author', 'Anonymous'),
        ]);

        return redirect()
            ->route('plugins.hello-world.greetings')
            ->with('success', 'Greeting added successfully!');
    }

    /**
     * Delete a greeting.
     */
    public function destroy(Greeting $greeting)
    {
        $greeting->delete();

        return redirect()
            ->route('plugins.hello-world.greetings')
            ->with('success', 'Greeting deleted!');
    }
}
