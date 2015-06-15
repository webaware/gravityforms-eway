module.exports = function (grunt) {

	grunt.initConfig({
		pkg: grunt.file.readJSON("package.json"),

		jshint: {
			all: [
				"Gruntfile.js",
				"js/*.js",
				"!js/*.min.js"
			],
			options: {
				jshintrc: "js/.jshintrc",
				force: true
			}
		},

		uglify: {
			build: {
				options: {
					preserveComments: "some"
				},
				files: [{
					expand: true,
					cwd: "js",
					dest: "js",
					src: [
						"*.js",
						"!*.min.js"
					],
					ext: '.min.js'
				}]
			}
		}

	});

	grunt.loadNpmTasks("grunt-contrib-jshint");
	grunt.loadNpmTasks("grunt-contrib-uglify");

	grunt.registerTask("default", [ "jshint" ]);

};
