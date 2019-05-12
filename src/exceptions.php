<?php
namespace ProfiCloS\Ares;

class Exception extends \Exception { }

class RuntimeException extends Exception { }

class ServerException extends RuntimeException { }

class ParseException extends RuntimeException { }

class InvalidArgumentException extends Exception { }

class NotFoundException extends InvalidArgumentException { }