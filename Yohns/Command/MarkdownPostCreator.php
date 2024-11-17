<?php

namespace Yohns\Command;

use JW3B\Helpful\Str;
use Symfony\Component\Yaml\Yaml;

/**
 * Class to create a new markdown post.
 * Supports creating a page, blog, or gallery post with frontmatter.
 */
class MarkdownPostCreator {

	/**
	 * The current date and time.
	 *
	 * @var string
	 */
	private string $date;

	/**
	 * Options for the post creator.
	 *
	 * @var array
	 */
	private array $options = [
		'timezone' => 'EST',
		'blogDirectory' => 'content/blog',
		'pageDirectory' => 'content/page',
		'galleryDirectory' => 'content/gallery',
	];

	/**
	 * The timezone for the post creator.
	 *
	 * @var \DateTimeZone
	 */
	private \DateTimeZone $timezone;

	/**
	 * Constructor.
	 *
	 * @param array $options Options for the post creator.
	 */
	public function __construct(array $options){
		$this->options = array_merge($this->options, $options);
		$this->timezone = new \DateTimeZone($this->options['timezone']);
		$this->date = $this->getCurrentDateTime();
	}

	/**
	 * Run the post creator.
	 *
	 * @return void
	 */
	public function run(): void {
		echo "What kind of post would you like to create?\n";
		echo "[1] Page\n";
		echo "[2] Blog\n";
		echo "[3] Gallery\n";

		$typeSelection = (int)$this->prompt("Select a post type by number: ");
		switch ($typeSelection) {
			case 1:
				$this->createPage();
				break;
			case 2:
				$this->createBlogOrGallery('blog');
				break;
			case 3:
				$this->createBlogOrGallery('gallery');
				break;
			default:
				echo "Invalid selection. Please select a valid number (1, 2, or 3).\n";
		}
	}

	/**
	 * Create a new page.
	 *
	 * @return void
	 */
	private function createPage(): void {
		$pageName = $this->prompt("Enter the page name: ");
		$description = $this->prompt("Enter a description: ");
		$tags = $this->prompt("Enter tags (comma-separated): ");
		$filePath = $this->options['pageDirectory'].'/'.Str::clean_url($pageName).'.md';

		$this->createDirectoryIfNotExists($this->options['pageDirectory']);

		$tagCallback = fn(string $k): string => str_replace(' ', '-', trim($k));
		$frontmatter = $this->createFrontmatter([
			'created' => $this->date,
			'title' => Str::headline($pageName),
			'description' => $description,
			'tags' => array_map($tagCallback, explode(',', $tags))
		]);

		file_put_contents($filePath, $frontmatter);
		$this->openFileInEditor($filePath);
		echo "Page created: $filePath\n";
	}

	/**
	 * Create a new blog or gallery post.
	 *
	 * @param string $type The type of post to create ('blog' or 'gallery').
	 * @return void
	 */
	private function createBlogOrGallery(string $type): void {
		$basePath = $type == 'blog' ? $this->options['blogDirectory'] : $this->options['galleryDirectory'];
		$subfolders = $this->listDirectories($basePath);

		$category = '';
		echo "Available categories:\n";
		echo "[0] Create a new category\n";
		foreach ($subfolders as $index => $folder) {
			echo "[" . ($index + 1) . "] " . basename($folder) . "\n";
		}

		$categoryIndex = (int)$this->prompt("Select a category by number: ");
		if ($categoryIndex === 0) {
			$category = $this->prompt("Enter a new category name: ");
			$this->createDirectoryIfNotExists("$basePath/$category");
		} elseif (isset($subfolders[$categoryIndex - 1])) {
			$category = basename($subfolders[$categoryIndex - 1]);
		} else {
			echo "Invalid selection. Exiting.\n";
			exit(1);
		}

		$title = $this->prompt("Enter the title: ");
		$description = $this->prompt("Enter a description: ");
		$tags = $this->prompt("Enter tags (comma-separated): ");

		$filePath = "$basePath/$category/" . Str::clean_url($title) . ".md";
		$tagCallback = fn(string $k): string => Str::kebab(trim($k));
		$frontmatter = $this->createFrontmatter([
			'created' => $this->date,
			'title' => Str::headline($title),
			'description' => $description,
			'tags' => array_map($tagCallback, explode(',', $tags))
		]);

		file_put_contents($filePath, $frontmatter);
		$this->openFileInEditor($filePath);
		echo "Post created: $filePath\n";
	}


	/**
	 * Prompt the user for input.
	 *
	 * @param string $message The message to display to the user.
	 * @return string The user's input.
	 */
	private function prompt(string $message): string {
		fwrite(STDOUT, $message);
		return trim(fgets(STDIN));
	}

	/**
	 * List directories in a given path.
	 *
	 * @param string $path The path to list directories from.
	 * @return array The list of directories.
	 */
	private function listDirectories(string $path): array {
		return array_filter(glob("$path/*"), 'is_dir');
	}

	/**
	 * Create a directory if it does not exist.
	 *
	 * @param string $path The path to the directory.
	 * @return void
	 */
	private function createDirectoryIfNotExists(string $path): void {
		if (!is_dir($path)) {
			mkdir($path, 0755, true);
		}
	}

	/**
	 * Create frontmatter for a post.
	 *
	 * @param array $data The data for the frontmatter.
	 * @return string The frontmatter as a string.
	 */
	private function createFrontmatter(array $data): string {
		$frontmatter = "---\n";
		$data['draft'] = true;
		$data['thumbnail'] = "";
		$frontmatter .= Yaml::dump($data);
		$frontmatter .= "---\n\n# ".Str::headline($data['title'])."\n\n";
		return $frontmatter;
	}

	/**
	 * Open a file in the default editor.
	 *
	 * @param string $filePath The path to the file.
	 * @return void
	 */
	private function openFileInEditor(string $filePath): void {
		exec("code \"$filePath\"");
	}

	/**
	 * Get the current date and time.
	 *
	 * @return string The current date and time.
	 */
	private function getCurrentDateTime(): string {
		$dateTime = new \DateTime('now', $this->timezone);
		return $dateTime->format('Y-m-d H:i:s');
	}
}
