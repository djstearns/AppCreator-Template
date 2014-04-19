#import "ApplicationMods.h"

@implementation ApplicationMods

+ (NSArray*) compiledMods
{
	NSMutableArray *modules = [NSMutableArray array];
	[modules addObject:[NSDictionary dictionaryWithObjectsAndKeys:@"scanditsdk",@"name",@"com.mirasense.scanditsdk",@"moduleid",@"1.3.1",@"version",@"75D3BDEC-E660-4727-BD86-D3962830BDDA",@"guid",@"",@"licensekey",nil]];
	return modules;
}

@end