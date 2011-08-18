default:
# Build c,read for your architecture by default
# requires libreadline-dev

	@echo 'Building c,read'
	g++ -o readline_wrapper readline_wrapper.cpp -lreadline

portable:
# Make a portable cross-distribution binary version
# build everything statically
# If you're compiling on an x64, you'll be stuck with x64

	@echo 'Building portable c,read'
	g++ -o readline_wrapper readline_wrapper.cpp -static-libgcc -static-libstdc++ -lreadline -lncurses -ldl -static
